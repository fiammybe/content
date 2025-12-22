[![License](https://img.shields.io/github/license/ImpressCMS/impresscms-module-content.svg?maxAge=2592000)](License.txt) 
	[![GitHub release](https://img.shields.io/github/release/ImpressCMS/impresscms-module-content.svg?maxAge=2592000)](https://github.com/ImpressCMS/impresscms-module-content/releases) 
		[![This is ImpressCMS module](https://img.shields.io/badge/ImpressCMS-module-F3AC03.svg?maxAge=2592000)](http://impresscms.org)

# Content Module

Content is the standard ImpressCMS Content Manager module, which is part of the core distribution. Using this module, you can manage textual content pages and their hierarchy.

## 🔒 Security Notice - Version 1.3.3

**CRITICAL SECURITY UPDATE** - Version 1.3.3 includes important security fixes addressing multiple vulnerabilities:

- ✅ SQL Injection vulnerabilities fixed
- ✅ Cross-Site Scripting (XSS) vulnerabilities fixed
- ✅ CSRF protection implemented
- ✅ Security headers added
- ✅ Input validation hardened

**All users should update immediately.** See [SECURITY_AUDIT.md](SECURITY_AUDIT.md) for complete details.

## Security

For security vulnerabilities, please see our [Security Policy](SECURITY.md).  
**Do not report security issues via public GitHub issues.**

## Compatibility

- **PHP**: 7.4 - 8.5 (tested and compatible)
- **ImpressCMS**: Latest version recommended
- **Database**: MySQL 5.7+ / MariaDB 10.2+

## Features

- Hierarchical content management
- SEO-friendly URLs
- Content permissions
- Comments support
- Tag support
- Breadcrumb navigation
- Block integration
- RSS feed support

## Installation

1. Extract the content module to `/modules/content/`
2. Install via ImpressCMS admin → System → Modules
3. Configure permissions in Group Manager
4. Start creating content pages!

## Documentation

- [Security Audit Report](SECURITY_AUDIT.md) - Comprehensive security analysis
- [Security Fixes Summary](SECURITY_FIXES.md) - Quick reference guide
- [Security Summary](SECURITY_SUMMARY.md) - High-level overview
- [Changelog](docs/changelog.md) - Version history
- [Installation Guide](docs/install.md) - Detailed setup instructions

## Updates

Updates of this module are available on the Github page - https://github.com/fiammybe/content

## Security & Compliance

This module follows:
- OWASP Top 10 2021 security guidelines
- ImpressCMS security best practices
- PHP secure coding standards

All code contributions are reviewed for security issues before merging.

## Contributing

Contributions are welcome! Please:
1. Follow existing code style
2. Add security considerations for any input handling
3. Include tests where applicable
4. Update documentation

## Support

- GitHub Issues: For bugs and feature requests
- ImpressCMS Forums: For general support
- Security Issues: security@impresscms.org (private)

## License

GNU General Public License v2.0 - See [License.txt](License.txt)