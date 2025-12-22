<?php
/**
 * Header page included at the begining of each page on user side of the mdoule
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id$
 */

include_once "../../mainfile.php";
include_once "include/common.php";

// Security fix: Add security headers to protect against various attacks
if (!headers_sent()) {
	// Prevent clickjacking attacks
	header("X-Frame-Options: SAMEORIGIN");
	// Prevent MIME-type sniffing
	header("X-Content-Type-Options: nosniff");
	// Enable XSS protection in browsers
	header("X-XSS-Protection: 1; mode=block");
	// Control referrer information
	header("Referrer-Policy: strict-origin-when-cross-origin");
}