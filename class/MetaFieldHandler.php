<?php
/**
 * MetaFieldHandler – IPF handler for zone meta values
 *
 * Provides helpers for loading, saving and deleting all meta values
 * that belong to a single content item in one call.
 *
 * @copyright  The ImpressCMS Project <https://www.impresscms.org>
 * @license    GNU General Public License (GPL) v2
 * @package    content
 * @since      1.4
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Handler for mod_content_MetaField objects.
 */
class mod_content_MetaFieldHandler extends icms_ipf_Handler {

	public function __construct(&$db) {
		parent::__construct($db, 'meta_field', 'meta_id', 'meta_zone_name', 'meta_value', 'content');
	}

	// -----------------------------------------------------------------------
	// Convenience helpers
	// -----------------------------------------------------------------------

	/**
	 * Return all meta values for a given module + item as a flat name→value map.
	 *
	 * @param  string $module   Module dirname (e.g. 'content')
	 * @param  int    $item_id  Owning object ID (e.g. content_id)
	 * @return array            ['zone_name' => 'stored_value', …]
	 */
	public function getMetaForItem($module, $item_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('meta_module',  $module));
		$criteria->add(new icms_db_criteria_Item('meta_item_id', (int) $item_id));

		$rows = $this->getObjects($criteria, false, false);
		$result = array();
		foreach ($rows as $row) {
			$result[$row['meta_zone_name']] = $row['meta_value'];
		}
		return $result;
	}

	/**
	 * Persist a full set of meta values for one item.
	 *
	 * Existing values for the item are deleted first; then every entry in
	 * $values is inserted as a fresh row.
	 *
	 * @param  string $module   Module dirname
	 * @param  int    $item_id  Owning object ID
	 * @param  array  $values   ['zone_name' => 'value', …]
	 * @return bool
	 */
	public function saveMetaForItem($module, $item_id, array $values) {
		$this->deleteMetaForItem($module, $item_id);

		foreach ($values as $zone_name => $value) {
			if ((string) $zone_name === '') {
				continue;
			}
			/** @var mod_content_MetaField $obj */
			$obj = $this->create(true);
			$obj->setVar('meta_module',    $module);
			$obj->setVar('meta_item_id',   (int) $item_id);
			$obj->setVar('meta_zone_name', (string) $zone_name);
			$obj->setVar('meta_value',     (string) $value);
			if (!$this->insert($obj, true)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Delete all meta values for one item.
	 *
	 * @param  string $module   Module dirname
	 * @param  int    $item_id  Owning object ID
	 * @return bool
	 */
	public function deleteMetaForItem($module, $item_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('meta_module',  $module));
		$criteria->add(new icms_db_criteria_Item('meta_item_id', (int) $item_id));
		return $this->deleteAll($criteria);
	}
}
