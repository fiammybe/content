# Security Policy

## Supported Versions

The following versions of the ImpressCMS Content Module are currently being supported with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.3.x   | :white_check_mark: |
| < 1.3   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability within this module, please send an email to security@impresscms.org. All security vulnerabilities will be promptly addressed.

**Please do not report security vulnerabilities through public GitHub issues.**

### What to Include

When reporting a vulnerability, please include:

1. Description of the vulnerability
2. Steps to reproduce
3. Potential impact
4. Suggested fix (if available)
5. Your contact information

### Response Timeline

- **Initial Response**: Within 48 hours
- **Vulnerability Confirmation**: Within 7 days
- **Fix Development**: Varies based on severity
- **Public Disclosure**: After patch is released and deployed

## Security Best Practices for Developers

### Input Validation

1. **Always validate and sanitize user input**
   ```php
   // Good
   $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
   
   // Bad
   $id = $_GET['id'];
   ```

2. **Use whitelisting for operations**
   ```php
   // Good
   $valid_ops = array('add', 'edit', 'delete');
   if (in_array($op, $valid_ops, true)) {
       // Process
   }
   ```

3. **Escape output based on context**
   ```php
   // HTML context
   echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
   
   // URL context
   echo urlencode($user_input);
   ```

### SQL Injection Prevention

1. **Use parameterized queries** (ImpressCMS Criteria objects)
2. **Never concatenate user input into SQL**
3. **Escape LIKE wildcards**
   ```php
   $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $input);
   ```

### XSS Prevention

1. **Escape all user-generated content**
2. **Use Content Security Policy (CSP) headers**
3. **Validate and sanitize rich text input**
4. **Never use `eval()` with user input**

### CSRF Protection

1. **Always use CSRF tokens for state-changing operations**
   ```php
   if (!icms::$security->check()) {
       redirect_header('index.php', 3, 'Security check failed');
       exit();
   }
   ```

2. **Include tokens in all forms**
3. **Validate on the server side**

### Authentication & Authorization

1. **Check permissions before operations**
   ```php
   if (!$contentObj->userCanEditAndDelete()) {
       redirect_header('index.php', 3, _NOPERM);
       exit();
   }
   ```

2. **Use secure session handling**
3. **Implement proper access controls**

### File Security

1. **Validate file types and sizes**
2. **Store uploads outside web root when possible**
3. **Use unique, random filenames**
4. **Scan uploads for malware**

### Error Handling

1. **Don't expose sensitive information in errors**
   ```php
   // Good
   error_log('Detailed error: ' . $error);
   echo 'An error occurred';
   
   // Bad
   echo 'Database error: ' . $db->error;
   ```

2. **Use centralized error logging**
3. **Configure display_errors=Off in production**

### Security Headers

Always include these headers (already implemented in header.php):

```php
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
```

## Security Testing

### Regular Testing Schedule

- **Code Review**: Before each release
- **Security Scanning**: Weekly (automated)
- **Penetration Testing**: Annually
- **Dependency Audit**: Monthly

### Tools Used

- CodeQL for static analysis
- OWASP ZAP for dynamic testing
- PHPStan for code quality
- Composer audit for dependencies

## Security Updates

Security updates are released as soon as possible after a vulnerability is confirmed and fixed. Updates are announced through:

1. GitHub Security Advisories
2. ImpressCMS Security Mailing List
3. Module Changelog

## Compliance

This module aims to comply with:

- OWASP Top 10 2021
- PHP Security Best Practices
- ImpressCMS Security Guidelines
- GDPR requirements (where applicable)

## Additional Resources

- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [ImpressCMS Security Guidelines](https://github.com/ImpressCMS/guidelines)
- [Security Audit Report](SECURITY_AUDIT.md)

## Version History

### 1.3.3 (2025-12-22)
- Fixed critical SQL injection vulnerabilities
- Fixed XSS vulnerabilities
- Added CSRF protection
- Implemented security headers
- Improved input validation

---

**Last Updated**: 2025-12-22
