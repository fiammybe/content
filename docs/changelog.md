# Content 1.3.3 (Security Update)
Release date : 22/12/2025

## Security Fixes (Critical Priority)
This release addresses multiple security vulnerabilities identified in a comprehensive security audit:

### Critical Vulnerabilities Fixed
- **SQL Injection in Content Retrieval**: Fixed improper sanitization of page parameters that could lead to SQL injection attacks
- **Input Validation**: Replaced deprecated `FILTER_SANITIZE_MAGIC_QUOTES` with proper sanitization methods

### High Severity Vulnerabilities Fixed
- **XSS in Error Messages**: Added proper HTML escaping for security error messages to prevent reflected XSS attacks
- **XSS in Content Tags**: Implemented proper URL encoding and HTML escaping in tag display to prevent stored XSS
- **CSRF Protection**: Added CSRF token validation to admin delete operations and field change operations
- **Input Sanitization**: Removed redundant and incorrect use of `htmlentities()` in admin input processing

### Medium Severity Vulnerabilities Fixed
- **SQL Injection in LIKE Clauses**: Added proper escaping of LIKE wildcard characters (%, _, \\) in ContentHandler
- **Path Traversal**: Enhanced path validation to prevent directory traversal attacks via PATH_INFO
- **Method Call Error**: Fixed incorrect method call syntax in `ContentHandler::updateCounter()`
- **Access Control**: Improved validation to ensure objects exist before performing operations

### Security Enhancements
- **Security Headers**: Added HTTP security headers to all pages:
  - X-Frame-Options: SAMEORIGIN (prevents clickjacking)
  - X-Content-Type-Options: nosniff (prevents MIME sniffing)
  - X-XSS-Protection: 1; mode=block (enables browser XSS protection)
  - Referrer-Policy: strict-origin-when-cross-origin (controls referrer information)

### Documentation
- Added comprehensive `SECURITY_AUDIT.md` documenting all 21 identified vulnerabilities
- Added `SECURITY.md` with security policy and best practices
- Documented security fixes in changelog

### Breaking Changes
None - all fixes are backward compatible

### Recommendations
- **Immediate Update Required**: This is a critical security update addressing SQL injection and XSS vulnerabilities
- Review custom code for similar patterns
- Ensure ImpressCMS core is up to date
- Monitor logs for suspicious activity

For detailed information about vulnerabilities and fixes, see `SECURITY_AUDIT.md`

---

# Content 1.4.0
Release date : 01/03/2022
- Update version information
- Version that is bundled with ImpressCMS 1.4.4

# Content 1.3.0
Release date : 24/12/2019
- Update version information
- Check if it works with PHP 7.3 (yes)

# Content 1.2.2
Release date: 10 Oct 2018
## Fixed
 * Module version number is now text, no longer number
 
# Content 1.2.1 Final
Release date: 30 Dec 2016
## Fixed
 * Missing delimiter in admin caused fatal error
 * #449 check for isNew before starting clone
 
# Content 1.2.0
Release date: 15 Sept 2013

## Fixed
* #12 edit Content > can not create a Symlink
* #246 false pagination
* #250 sometimes no function for comment
* #282 Clone of content also clones pageviews
* #409 bug with content pagination
* #434 Content Menu Block of Module "Content" generates links that wont show comment feature
