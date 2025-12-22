# Security Audit Report - ImpressCMS Content Module

**Date:** 2025-12-22  
**Auditor:** Senior Application Security Auditor  
**Module:** ImpressCMS Content Module  
**PHP Version:** 7.4 - 8.5  
**Framework:** ImpressCMS  

---

## Executive Summary

This report presents a comprehensive security audit of the ImpressCMS Content Module. The audit identified **multiple critical and high-severity vulnerabilities** across various categories including SQL injection, Cross-Site Scripting (XSS), insufficient input validation, and authentication/authorization issues.

**Overall Risk Level:** 🔴 **CRITICAL**

---

## Detailed Findings

### 1. SQL Injection Vulnerabilities

#### 1.1 CRITICAL - SQL Injection in Content Retrieval
**File:** `content.php` (Line 47, 53, 67)  
**Severity:** 🔴 **CRITICAL**  
**OWASP Category:** A03:2021 – Injection

**Vulnerable Code:**
```php
// Line 47
$page = isset($_GET['page']) ? trim(StopXSS($_GET['page'])) : ((isset($_POST['page'])) ? trim(StopXSS($_POST['page'])) : "");

// Line 53
$path = trim(StopXSS($path));

// Line 67
$page = is_int($page) ? (int)$page : urlencode($page);
$criteria = $content_content_handler->getContentsCriteria(0, 1, false, false, $page, false, 'content_id', 'DESC');
```

**Issue:**  
The `$page` variable is obtained from user input (`$_GET['page']` or `$_POST['page']`) and only sanitized with `StopXSS()`. While it's later passed through `urlencode()`, it's then used in `getContentsCriteria()` which constructs SQL queries. The function uses LIKE clauses that could be exploited if the StopXSS function doesn't properly escape SQL metacharacters.

**Exploitation:**
```
GET /content.php?page=%' OR '1'='1
```

**Fix:**
```php
// Use parameterized queries and proper type validation
$page = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$page = isset($_POST['page']) ? filter_input(INPUT_POST, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : $page;

// Validate format
if ($page && !preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
    $page = '';
}
```

---

#### 1.2 HIGH - SQL Injection in Tag Search
**File:** `index.php` (Line 26)  
**Severity:** 🟠 **HIGH**  
**OWASP Category:** A03:2021 – Injection

**Vulnerable Code:**
```php
$clean_content_tags = isset($_GET['tag']) ? filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_MAGIC_QUOTES) : false;
```

**Issue:**  
`FILTER_SANITIZE_MAGIC_QUOTES` is deprecated and doesn't provide adequate SQL injection protection. This value is passed to database queries via `getContents()` method.

**Fix:**
```php
$clean_content_tags = isset($_GET['tag']) ? filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : false;
// Additional validation
if ($clean_content_tags && strlen($clean_content_tags) > 100) {
    $clean_content_tags = false;
}
```

---

#### 1.3 MEDIUM - Potential SQL Injection in ContentHandler
**File:** `class/ContentHandler.php` (Line 145, 148)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A03:2021 – Injection

**Vulnerable Code:**
```php
if ($content_tags) $criteria->add(new icms_db_criteria_Item('content_tags', '%'.$content_tags.'%', 'LIKE'));

$crit = new icms_db_criteria_Compo(new icms_db_criteria_Item('short_url', $content_id,'LIKE'));
```

**Issue:**  
Direct concatenation of user input into LIKE clauses without proper escaping of wildcard characters.

**Fix:**
```php
// Escape LIKE wildcards before use
if ($content_tags) {
    $escaped_tags = str_replace(['%', '_'], ['\\%', '\\_'], $content_tags);
    $criteria->add(new icms_db_criteria_Item('content_tags', '%'.$escaped_tags.'%', 'LIKE'));
}
```

---

### 2. Cross-Site Scripting (XSS) Vulnerabilities

#### 2.1 HIGH - Reflected XSS in Error Messages
**File:** `content.php` (Line 99, 111)  
**Severity:** 🟠 **HIGH**  
**OWASP Category:** A03:2021 – Injection (XSS)

**Vulnerable Code:**
```php
redirect_header(icms_getPreviousPage('index.php'), 3, _MD_CONTENT_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
```

**Issue:**  
Security error messages are directly concatenated with `implode()` without HTML escaping, potentially allowing XSS if error messages contain user input.

**Fix:**
```php
$errors = array_map('htmlspecialchars', icms::$security->getErrors());
redirect_header(icms_getPreviousPage('index.php'), 3, _MD_CONTENT_SECURITY_CHECK_FAILED . implode('<br />', $errors));
```

---

#### 2.2 HIGH - Stored XSS in Content Body
**File:** `class/Content.php` (Line 35)  
**Severity:** 🟠 **HIGH**  
**OWASP Category:** A03:2021 – Injection (XSS)

**Vulnerable Code:**
```php
$this->quickInitVar('content_body', XOBJ_DTYPE_TXTAREA);
```

**Issue:**  
The content body uses TXTAREA type but doesn't enforce proper output encoding. While ImpressCMS may handle this, there's no explicit sanitization shown in the rendering logic.

**Recommendation:**  
Ensure all output uses appropriate escaping:
- Use `htmlspecialchars()` for HTML context
- Use appropriate escaping for JavaScript/CSS contexts
- Consider implementing Content Security Policy (CSP)

---

#### 2.3 MEDIUM - XSS in Content Tags Display
**File:** `class/Content.php` (Line 134-145)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A03:2021 – Injection (XSS)

**Vulnerable Code:**
```php
function content_tags() {
    if ($this->getVar('content_tags', 'e') != '') {
        $tags = explode (',', $this->getVar('content_tags', 'e'));
        foreach ($tags as $k => $tag) {
            $tag = trim ($tag);
            $tag = ' <a href="' . $this->handler->_moduleUrl . 'index.php?tag=' . $tag . '">' . $tag . '</a>';
            $tags[$k] = $tag;
        }
        return implode(',', $tags);
    }
}
```

**Issue:**  
Tags are not URL-encoded or HTML-escaped before being inserted into href attributes and displayed as link text. An attacker could inject malicious tags.

**Fix:**
```php
function content_tags() {
    if ($this->getVar('content_tags', 'e') != '') {
        $tags = explode (',', $this->getVar('content_tags', 'e'));
        foreach ($tags as $k => $tag) {
            $tag = trim($tag);
            $encoded_tag = urlencode($tag);
            $escaped_tag = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
            $tag = ' <a href="' . htmlspecialchars($this->handler->_moduleUrl, ENT_QUOTES, 'UTF-8') . 
                    'index.php?tag=' . $encoded_tag . '">' . $escaped_tag . '</a>';
            $tags[$k] = $tag;
        }
        return implode(',', $tags);
    }
    return false;
}
```

---

### 3. Cross-Site Request Forgery (CSRF) Issues

#### 3.1 HIGH - Missing CSRF Token Validation on Delete
**File:** `admin/content.php` (Line 108)  
**Severity:** 🟠 **HIGH**  
**OWASP Category:** A01:2021 – Broken Access Control

**Vulnerable Code:**
```php
if((isset($_POST['confirm']) && $_POST['confirm'] === TRUE) || !count($subs)) {
    $controller = new icms_ipf_Controller($content_content_handler);
    $controller->handleObjectDeletion();
}
```

**Issue:**  
The deletion confirmation only checks for `$_POST['confirm']` but doesn't verify CSRF token before deletion. While there's a confirmation dialog, the actual deletion lacks token validation.

**Fix:**
```php
if((isset($_POST['confirm']) && $_POST['confirm'] === TRUE) || !count($subs)) {
    if (!icms::$security->check()) {
        redirect_header('content.php', 3, _MD_CONTENT_SECURITY_CHECK_FAILED);
        exit();
    }
    $controller = new icms_ipf_Controller($content_content_handler);
    $controller->handleObjectDeletion();
}
```

---

#### 3.2 MEDIUM - CSRF in Field Change Operation
**File:** `admin/content.php` (Line 135-150)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A01:2021 – Broken Access Control

**Vulnerable Code:**
```php
case "changedField" :
    foreach ($_POST['mod_content_Content_objects'] as $k=>$v){
        // ... process changes without CSRF check
```

**Issue:**  
No CSRF token validation before processing field changes.

**Fix:**
```php
case "changedField" :
    if (!icms::$security->check()) {
        redirect_header('content.php', 3, _AM_CONTENT_SECURITY_CHECK_FAILED);
        exit();
    }
    foreach ($_POST['mod_content_Content_objects'] as $k=>$v){
```

---

### 4. Authentication & Authorization Vulnerabilities

#### 4.1 MEDIUM - Insufficient Access Control Validation
**File:** `content.php` (Line 106-108)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A01:2021 – Broken Access Control

**Vulnerable Code:**
```php
case "del":
    if (!$contentObj->userCanEditAndDelete()) {
        redirect_header($contentObj->getItemLink(true), 3, _NOPERM);
    }
    if (isset($_POST['confirm'])) {
```

**Issue:**  
The access check happens but doesn't exit immediately, allowing code execution to continue. Also, `$contentObj` may not be initialized properly if `$clean_content_id` is invalid.

**Fix:**
```php
case "del":
    $contentObj = $content_content_handler->get($clean_content_id);
    if (!$contentObj || $contentObj->isNew() || !$contentObj->userCanEditAndDelete()) {
        redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
        exit();
    }
    if (isset($_POST['confirm'])) {
```

---

#### 4.2 LOW - Insecure Direct Object Reference
**File:** `content.php` (Line 64-65, 90)  
**Severity:** 🔵 **LOW**  
**OWASP Category:** A01:2021 – Broken Access Control

**Vulnerable Code:**
```php
if ($clean_content_id != 0) {
    $contentObj = $content_content_handler->get($clean_content_id);
}
```

**Issue:**  
While content ID is validated, there's no immediate check if the retrieved object is accessible by the current user before the operation selection.

**Recommendation:**  
Add access control checks immediately after object retrieval for all operations.

---

### 5. Input Validation Issues

#### 5.1 HIGH - Inadequate Input Sanitization
**File:** `admin/content.php` (Lines 69-79)  
**Severity:** 🟠 **HIGH**  
**OWASP Category:** A03:2021 – Injection

**Vulnerable Code:**
```php
if (isset($_GET ['op'])) {
    $clean_op = htmlentities($_GET ['op']);
}
if (isset($_POST ['op'])) {
    $clean_op = htmlentities($_POST ['op']);
}

$clean_content_id = isset($_GET ['content_id']) ?(int)htmlentities($_GET ['content_id']) : 0;
```

**Issue:**  
1. Using `htmlentities()` on operation codes is unnecessary and doesn't prevent attacks
2. Applying `htmlentities()` then casting to `(int)` is redundant - the cast should happen first
3. Double encoding could cause logic errors

**Fix:**
```php
$clean_op = isset($_GET['op']) ? $_GET['op'] : '';
$clean_op = isset($_POST['op']) ? $_POST['op'] : $clean_op;
// Op is validated against whitelist later, no need for htmlentities

$clean_content_id = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;
$clean_content_id = isset($_POST['content_id']) ? (int)$_POST['content_id'] : $clean_content_id;
$clean_content_pid = isset($_GET['content_pid']) ? (int)$_GET['content_pid'] : 0;
$clean_content_pid = isset($_POST['content_pid']) ? (int)$_POST['content_pid'] : $clean_content_pid;
```

---

#### 5.2 MEDIUM - Path Traversal Risk
**File:** `content.php` (Lines 50-62)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A01:2021 – Broken Access Control

**Vulnerable Code:**
```php
$path = (isset($_SERVER['PATH_INFO']) && substr($_SERVER['PATH_INFO'], 0, 1) == '/') ?
    substr($_SERVER['PATH_INFO'], 1, strlen($_SERVER['PATH_INFO'])) :
    ((isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : '');
$path = trim(StopXSS($path));
```

**Issue:**  
`$_SERVER['PATH_INFO']` is used directly with only XSS filtering. No validation for path traversal characters like `../` or null bytes.

**Fix:**
```php
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
if (substr($path, 0, 1) == '/') {
    $path = substr($path, 1);
}
// Remove path traversal attempts
$path = str_replace(['..', "\0", '\\'], '', $path);
$path = trim(filter_var($path, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
```

---

#### 5.3 MEDIUM - Insufficient Email Validation
**File:** `class/ContentHandler.php` (Line 278)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A03:2021 – Injection

**Vulnerable Code:**
```php
if (!is_object(icms::$user) || (!$content_isAdmin && $contentObj->getVar('content_uid', 'e') != icms::$user->uid ())) {
```

**Issue:**  
Calling `icms::$user->uid()` with parentheses - should be `->uid` (property access) not `->uid()` (method call). This could cause PHP errors.

**Fix:**
```php
if (!is_object(icms::$user) || (!$content_isAdmin && $contentObj->getVar('content_uid', 'e') != icms::$user->getVar('uid'))) {
```

---

### 6. Insecure Session Handling

#### 6.1 LOW - No Session Fixation Protection
**Severity:** 🔵 **LOW**  
**OWASP Category:** A07:2021 – Identification and Authentication Failures

**Issue:**  
The module relies on ImpressCMS core for session management but doesn't explicitly regenerate session IDs on privilege escalation.

**Recommendation:**  
Ensure the core framework implements `session_regenerate_id(true)` after authentication and privilege changes.

---

### 7. Information Disclosure

#### 7.1 MEDIUM - Verbose Error Messages
**File:** `content.php` (Line 99, 111)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A05:2021 – Security Misconfiguration

**Vulnerable Code:**
```php
redirect_header(icms_getPreviousPage('index.php'), 3, _MD_CONTENT_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
```

**Issue:**  
Detailed error messages exposed to users could reveal system internals.

**Fix:**
```php
// Log detailed errors server-side
error_log('Security check failed: ' . implode(', ', icms::$security->getErrors()));
// Show generic message to user
redirect_header(icms_getPreviousPage('index.php'), 3, _MD_CONTENT_SECURITY_CHECK_FAILED);
```

---

#### 7.2 LOW - Comments in Production Code
**File:** Multiple files  
**Severity:** 🔵 **LOW**  
**OWASP Category:** A05:2021 – Security Misconfiguration

**Issue:**  
Numerous code comments could reveal implementation details to attackers reviewing source.

**Recommendation:**  
Remove or minimize comments in production deployments.

---

### 8. Database Security Issues

#### 8.1 MEDIUM - Lack of Prepared Statements Verification
**File:** `class/ContentHandler.php` (Various lines)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A03:2021 – Injection

**Issue:**  
While the code uses the ImpressCMS framework's criteria objects, it's not clear if these always use prepared statements. Manual verification needed.

**Recommendation:**  
- Verify all database queries use parameterized/prepared statements
- Never concatenate user input into SQL
- Use ORM features consistently

---

### 9. Business Logic Vulnerabilities

#### 9.1 MEDIUM - Race Condition in Counter Update
**File:** `class/ContentHandler.php` (Line 272-285)  
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A04:2021 – Insecure Design

**Vulnerable Code:**
```php
public function updateCounter($id) {
    $contentObj = $this->get($id);
    if (!is_object($contentObj)) return false;
    
    if (!is_object(icms::$user) || (!$content_isAdmin && $contentObj->getVar('content_uid', 'e') != icms::$user->uid ())) {
        $contentObj->updating_counter = true;
        $contentObj->setVar('counter', $contentObj->getVar('counter', 'n') + 1);
        $this->insert($contentObj, true);
    }
```

**Issue:**  
Read-modify-write operation without transaction or atomic update. Concurrent requests could result in inaccurate counter values.

**Fix:**
```php
public function updateCounter($id) {
    global $content_isAdmin;
    
    $contentObj = $this->get($id);
    if (!is_object($contentObj)) return false;
    
    if (!is_object(icms::$user) || (!$content_isAdmin && $contentObj->getVar('content_uid', 'e') != icms::$user->getVar('uid'))) {
        // Use atomic SQL UPDATE instead of read-modify-write
        $sql = "UPDATE " . $this->table . " SET counter = counter + 1 WHERE content_id = ?";
        $this->db->query($sql, array($id));
    }
    
    return true;
}
```

---

#### 9.2 LOW - No Rate Limiting
**Severity:** 🔵 **LOW**  
**OWASP Category:** A04:2021 – Insecure Design

**Issue:**  
No rate limiting on content creation, deletion, or viewing operations. Could be abused for:
- Resource exhaustion
- Counter manipulation
- Spam content creation

**Recommendation:**  
Implement rate limiting on:
- Content submission (per user/IP)
- Page view counter updates
- Search queries
- Authentication attempts

---

### 10. Missing Security Headers

#### 10.1 MEDIUM - No Content Security Policy
**Severity:** 🟡 **MEDIUM**  
**OWASP Category:** A05:2021 – Security Misconfiguration

**Issue:**  
No CSP headers to mitigate XSS attacks.

**Recommendation:**
```php
// Add to header.php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
```

---

### 11. Dependency Risks

#### 11.1 LOW - Framework Version Uncertainty
**Severity:** 🔵 **LOW**  
**OWASP Category:** A06:2021 – Vulnerable and Outdated Components

**Issue:**  
Module depends on ImpressCMS core but doesn't specify minimum secure version.

**Recommendation:**  
- Document minimum ImpressCMS version
- Check for known vulnerabilities in dependencies
- Implement automated security scanning

---

## Summary Table

| # | Vulnerability | Severity | OWASP Category | Status |
|---|---------------|----------|----------------|--------|
| 1.1 | SQL Injection in Content Retrieval | 🔴 Critical | A03:2021 | 🔧 Fix Required |
| 1.2 | SQL Injection in Tag Search | 🟠 High | A03:2021 | 🔧 Fix Required |
| 1.3 | SQL Injection in ContentHandler | 🟡 Medium | A03:2021 | 🔧 Fix Required |
| 2.1 | Reflected XSS in Error Messages | 🟠 High | A03:2021 | 🔧 Fix Required |
| 2.2 | Stored XSS in Content Body | 🟠 High | A03:2021 | ⚠️ Review Required |
| 2.3 | XSS in Content Tags Display | 🟡 Medium | A03:2021 | 🔧 Fix Required |
| 3.1 | Missing CSRF on Delete | 🟠 High | A01:2021 | 🔧 Fix Required |
| 3.2 | CSRF in Field Change | 🟡 Medium | A01:2021 | 🔧 Fix Required |
| 4.1 | Insufficient Access Control | 🟡 Medium | A01:2021 | 🔧 Fix Required |
| 4.2 | Insecure Direct Object Reference | 🔵 Low | A01:2021 | ⚠️ Review Required |
| 5.1 | Inadequate Input Sanitization | 🟠 High | A03:2021 | 🔧 Fix Required |
| 5.2 | Path Traversal Risk | 🟡 Medium | A01:2021 | 🔧 Fix Required |
| 5.3 | Method Call Error | 🟡 Medium | A03:2021 | 🔧 Fix Required |
| 6.1 | No Session Fixation Protection | 🔵 Low | A07:2021 | ⚠️ Review Required |
| 7.1 | Verbose Error Messages | 🟡 Medium | A05:2021 | 🔧 Fix Required |
| 7.2 | Comments in Production | 🔵 Low | A05:2021 | 📝 Note |
| 8.1 | Prepared Statements | 🟡 Medium | A03:2021 | ⚠️ Verify |
| 9.1 | Race Condition in Counter | 🟡 Medium | A04:2021 | 🔧 Fix Required |
| 9.2 | No Rate Limiting | 🔵 Low | A04:2021 | 💡 Enhancement |
| 10.1 | Missing Security Headers | 🟡 Medium | A05:2021 | 🔧 Fix Required |
| 11.1 | Framework Version | 🔵 Low | A06:2021 | 📝 Documentation |

**Total:** 21 findings  
**Critical:** 1 | **High:** 4 | **Medium:** 11 | **Low:** 5

---

## Recommended Remediation Priority

### Priority 1 (Immediate - Critical/High)
1. **SQL Injection in Content Retrieval** (1.1)
2. **SQL Injection in Tag Search** (1.2)
3. **XSS in Error Messages** (2.1)
4. **Missing CSRF on Delete** (3.1)
5. **Inadequate Input Sanitization** (5.1)

### Priority 2 (Short-term - High/Medium)
6. **SQL Injection in ContentHandler** (1.3)
7. **XSS in Tags Display** (2.3)
8. **CSRF in Field Change** (3.2)
9. **Insufficient Access Control** (4.1)
10. **Path Traversal Risk** (5.2)
11. **Method Call Error** (5.3)
12. **Verbose Error Messages** (7.1)
13. **Race Condition** (9.1)
14. **Missing Security Headers** (10.1)

### Priority 3 (Long-term - Low/Enhancements)
15. All remaining Low severity findings
16. Implement rate limiting
17. Dependency management improvements

---

## Missing Architectural Safeguards

1. **No Rate Limiting**: Implement throttling for:
   - Content submissions
   - Search queries
   - Page views
   - Authentication attempts

2. **CSRF Token Management**: 
   - Ensure all state-changing operations validate CSRF tokens
   - Use framework's built-in security mechanisms consistently

3. **Prepared Statements**:
   - Audit all database queries
   - Ensure 100% parameterized query usage
   - Never concatenate user input into SQL

4. **Content Security Policy**:
   - Implement strict CSP headers
   - Restrict inline scripts
   - Define trusted sources

5. **Input Validation Framework**:
   - Implement centralized validation
   - Use whitelist approach
   - Validate data types, lengths, and formats

6. **Output Encoding**:
   - Context-aware output encoding
   - HTML, JavaScript, CSS, URL encoding as appropriate
   - Use templating engine safely

7. **Logging and Monitoring**:
   - Log security events
   - Monitor for attack patterns
   - Implement alerting

8. **Security Headers**:
   - X-Frame-Options
   - X-Content-Type-Options
   - X-XSS-Protection
   - Strict-Transport-Security

---

## Compliance Notes

**OWASP Top 10 2021 Coverage:**
- ✅ A01 Broken Access Control - Multiple findings
- ✅ A03 Injection - SQL Injection & XSS findings
- ✅ A04 Insecure Design - Race conditions
- ✅ A05 Security Misconfiguration - Headers, errors
- ⚠️ A06 Vulnerable Components - Framework dependency
- ✅ A07 Authentication Failures - Session handling

**Recommendations Align With:**
- ImpressCMS Security Guidelines
- OWASP Secure Coding Practices
- PHP Security Best Practices (PHP 7.4-8.5)

---

## Conclusion

The ImpressCMS Content Module requires **immediate security remediation** due to critical SQL injection and XSS vulnerabilities. The module would benefit from:

1. Comprehensive input validation refactoring
2. Consistent CSRF protection
3. Enhanced access control checks
4. Security headers implementation
5. Rate limiting and monitoring

**Estimated Remediation Effort:** 40-60 hours  
**Recommended Review Cycle:** Security audit every 6 months

---

**Report End**
