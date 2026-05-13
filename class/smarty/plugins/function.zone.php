<?php
/**
 * Smarty function plugin: {zone}
 *
 * Renders a zone placeholder in a template.
 *
 * In **frontend** mode the stored meta value for the named zone is returned
 * (read from the content object that Smarty has in scope).
 * In **admin / edit** mode (or when no content object is available) an empty
 * string is returned so that the tag does not produce output during backend
 * rendering or template scanning.
 *
 * Usage in template:
 *   <{zone name="subtitle"  type="text"   label="Subtitle"}>
 *   <{zone name="hero_img"  type="image"  label="Hero Image"}>
 *   <{zone name="cta_style" type="select" label="CTA Style" options="primary,secondary"}>
 *
 * Parameters:
 *   name    (required) – unique identifier for this zone within the template
 *   type    (optional, default "text") – text | textarea | select | image
 *   label   (optional) – human-readable label shown in the admin form
 *   options (optional) – comma-separated list of choices for type="select"
 *
 * @copyright  The ImpressCMS Project <https://www.impresscms.org>
 * @license    GNU General Public License (GPL) v2
 * @package    content
 * @since      1.4
 *
 * @param  array                                    $params   Tag parameters
 * @param  \Smarty\Template|\Smarty_Internal_Template $template Smarty template object
 * @return string
 */
function smarty_function_zone(array $params, $template): string {
	if (empty($params['name'])) {
		return '';
	}

	$zoneName = (string) $params['name'];

	/*
	 * Retrieve the content array that content.php assigns as 'content_content'.
	 * This contains the '_meta' sub-array populated by Content::toArray().
	 */
	$contentData = null;
	if (method_exists($template, 'getTemplateVars')) {
		$contentData = $template->getTemplateVars('content_content');
	} elseif (is_callable(array($template, 'getVariable'))) {
		/* Older Smarty 3 path used in some ImpressCMS builds. */
		try {
			$var = $template->getVariable('content_content');
			$contentData = is_object($var) ? $var->value : null;
		} catch (Exception $e) {
			$contentData = null;
		}
	}

	if (is_array($contentData) && isset($contentData['_meta'][$zoneName])) {
		return htmlspecialchars((string) $contentData['_meta'][$zoneName], ENT_QUOTES, 'UTF-8');
	}

	return '';
}
