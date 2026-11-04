<?php
/**
 * MetaField – IPF object for storing zone meta values
 *
 * Each row holds one named zone value for one content item.
 *
 * @copyright  The ImpressCMS Project <https://www.impresscms.org>
 * @license    GNU General Public License (GPL) v2
 * @package    content
 * @since      1.4
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Stores a single zone meta value for a content item.
 *
 * @property int    $meta_id        Primary key (auto-increment)
 * @property string $meta_module    Module dirname that owns this value
 * @property int    $meta_item_id   ID of the owning object (e.g. content_id)
 * @property string $meta_zone_name Name attribute of the zone
 * @property string $meta_value     Stored value
 */
class mod_content_MetaField extends icms_ipf_Object {

	public function __construct(&$handler) {
		icms_ipf_Object::__construct($handler);

		$this->quickInitVar('meta_id',        XOBJ_DTYPE_INT,    true);
		$this->quickInitVar('meta_module',     XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('meta_item_id',    XOBJ_DTYPE_INT,    true);
		$this->quickInitVar('meta_zone_name',  XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('meta_value',      XOBJ_DTYPE_TXTAREA, false);

		/* These fields are purely storage – never show in a form. */
		$this->hideFieldFromForm(array('meta_id', 'meta_module', 'meta_item_id', 'meta_zone_name', 'meta_value'));
		$this->hideFieldFromSingleView(array('meta_id', 'meta_module', 'meta_item_id', 'meta_zone_name', 'meta_value'));
	}
}
