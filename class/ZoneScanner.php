<?php
/**
 * ZoneScanner – parse Smarty templates for {zone} tags
 *
 * Scans a template file (and any files it includes recursively) for
 * ImpressCMS zone tags of the form:
 *
 *   <{zone name="…" type="…" label="…" [options="…"]}>
 *
 * and returns a structured list of all zone definitions found.
 *
 * @copyright  The ImpressCMS Project <https://www.impresscms.org>
 * @license    GNU General Public License (GPL) v2
 * @package    content
 * @since      1.4
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Scans ImpressCMS / Smarty templates for zone definitions.
 *
 * Usage:
 *   $scanner = new mod_content_ZoneScanner();
 *   $zones   = $scanner->scanTemplate('content_default.html');
 */
class mod_content_ZoneScanner {

	/** @var string[] Ordered list of directories to search for template files */
	private $templateDirs = array();

	/** @var string[] Files already scanned in the current run (prevent infinite loops) */
	private $visited = array();

	// -----------------------------------------------------------------------
	// Construction
	// -----------------------------------------------------------------------

	/**
	 * @param string[]|null $templateDirs
	 *   Ordered list of directories to look for template files.
	 *   Defaults to:  1. active theme,  2. module templates.
	 */
	public function __construct(array $templateDirs = null) {
		if ($templateDirs !== null) {
			$this->templateDirs = $templateDirs;
			return;
		}

		/* Build the default search order. */
		$dirs = array();

		/* 1. Active theme override (may not exist on fresh installs). */
		if (defined('ICMS_ROOT_PATH')) {
			global $icmsConfig;
			$themeSet = isset($icmsConfig['theme_set']) ? $icmsConfig['theme_set'] : '';
			if ($themeSet !== '') {
				$dirs[] = ICMS_ROOT_PATH . '/themes/' . $themeSet . '/templates/';
			}
		}

		/* 2. Module templates. */
		if (defined('CONTENT_ROOT_PATH')) {
			$dirs[] = CONTENT_ROOT_PATH . 'templates/';
		} elseif (defined('ICMS_ROOT_PATH')) {
			$dirs[] = ICMS_ROOT_PATH . '/modules/content/templates/';
		}

		$this->templateDirs = $dirs;
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Scan a template file and return all zone definitions found.
	 *
	 * Resets the visited-files list on every top-level call so the same
	 * scanner instance can be reused.
	 *
	 * @param  string $filename  Template filename (not a full path; just the basename)
	 * @return array[]           List of zone definitions, each:
	 *                           ['name'=>'…','type'=>'text','label'=>'…','options'=>'']
	 */
	public function scanTemplate($filename) {
		$this->visited = array();
		return $this->_scanFile($filename);
	}

	// -----------------------------------------------------------------------
	// Internal helpers
	// -----------------------------------------------------------------------

	/**
	 * Locate a template file in the configured template directories.
	 *
	 * @param  string      $filename
	 * @return string|null Absolute path or null if not found
	 */
	private function _resolvePath($filename) {
		/* Strip a leading "file:" prefix that ImpressCMS sometimes uses. */
		$filename = preg_replace('/^file:/', '', $filename);
		$filename = ltrim($filename, '/\\');

		/* If an absolute path was provided, use it directly. */
		if (file_exists($filename)) {
			return $filename;
		}

		foreach ($this->templateDirs as $dir) {
			$candidate = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($candidate)) {
				return $candidate;
			}
		}
		return null;
	}

	/**
	 * Recursively scan a single file.
	 *
	 * @param  string  $filename
	 * @return array[]
	 */
	private function _scanFile($filename) {
		$path = $this->_resolvePath($filename);
		if ($path === null || isset($this->visited[$path])) {
			return array();
		}

		$this->visited[$path] = true;
		$content = @file_get_contents($path);
		if ($content === false) {
			return array();
		}

		$zones = array();

		/* --- collect zone tags ------------------------------------------- */
		/* Matches:  <{zone name="…" type="…" label="…" [options="…"]}> */
		if (preg_match_all('/<\{\s*zone\b([^}]*)\}>/', $content, $zoneMatches)) {
			foreach ($zoneMatches[1] as $attrString) {
				$zone = $this->_parseAttributes($attrString);
				if (!empty($zone['name'])) {
					$zone += array('type' => 'text', 'label' => $zone['name'], 'options' => '');
					$zones[] = $zone;
				}
			}
		}

		/* --- follow includes --------------------------------------------- */
		/* Matches:  <{include file="…"}> or <{include file='…'}> */
		if (preg_match_all('/<\{\s*include\b[^}]*\bfile\s*=\s*["\']([^"\']+)["\']/i', $content, $inclMatches)) {
			foreach ($inclMatches[1] as $incFile) {
				$zones = array_merge($zones, $this->_scanFile($incFile));
			}
		}

		return $zones;
	}

	/**
	 * Parse an attribute string like  name="foo" type="text" label="Foo"
	 * into a key→value array.
	 *
	 * Handles both double-quoted and single-quoted values.
	 *
	 * @param  string $attrString
	 * @return array
	 */
	private function _parseAttributes($attrString) {
		$result = array();
		$pattern = '/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/';
		if (preg_match_all($pattern, $attrString, $m)) {
			foreach ($m[1] as $i => $key) {
				/* Group 2 is double-quoted value, group 3 is single-quoted. */
				$result[strtolower($key)] = ($m[2][$i] !== '') ? $m[2][$i] : $m[3][$i];
			}
		}
		return $result;
	}
}
