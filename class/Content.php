<?php
/**
 * ImpressCMS Conent Persistable Class
 *
 * @since 		ImpressCMS 1.2
 * @copyright 	The ImpressCMS Project <http://www.impresscms.org>
 * @license		GNU General Public License (GPL) <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @author		Rodrigo P Lima (aka TheRplima) <therplima@impresscms.org>
 * @version		$Id$
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * ImpressCMS Core Content Object Class
 *
 * @since ImpressCMS 1.2
 * @author Rodrigo P Lima (aka TheRplima) <therplima@impresscms.org>
 */
class mod_content_Content extends icms_ipf_seo_Object {
	private $_poster_info = false;
	public $updating_counter = false;
	public $tags = false;
	public $categories = false;

	/**
	 * Lazy-loaded zone meta values for this content item.
	 * Populated by loadMeta() and persisted via ContentHandler::afterSave().
	 *
	 * @var array|null  null = not yet loaded,  array = name→value map
	 */
	public $_meta = null;

	/**
	 * Pending meta values collected from a submitted form before afterSave() runs.
	 *
	 * @var array
	 */
	public $_pendingMeta = array();

	public function __construct(&$handler) {
		global $contentConfig;

		icms_ipf_Object::__construct($handler);

		$this->quickInitVar('content_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('content_pid', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('content_uid', XOBJ_DTYPE_INT, true, false, false, 1);
		$this->quickInitVar('content_title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('content_body', XOBJ_DTYPE_TXTAREA);
		$this->quickInitVar('content_css', XOBJ_DTYPE_TXTAREA);
		$this->quickInitVar('content_tags', XOBJ_DTYPE_TXTAREA);
		$this->quickInitVar('content_visibility', XOBJ_DTYPE_INT, true, false, false, CONTENT_CONTENT_VISIBLE_MENUSUBS);
		$this->quickInitVar('content_published_date', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('content_updated_date', XOBJ_DTYPE_LTIME);
		$this->quickInitVar('content_weight', XOBJ_DTYPE_INT, true, false, false, 0);
		$this->quickInitVar('content_status', XOBJ_DTYPE_INT, true, false, false, CONTENT_CONTENT_STATUS_PUBLISHED);
		$this->quickInitVar('content_makesymlink', XOBJ_DTYPE_INT, true, false, false, 0);
		$this->quickInitVar('content_showsubs', XOBJ_DTYPE_INT, false, false, false, $contentConfig['show_relateds']);
		$this->quickInitVar('content_cancomment', XOBJ_DTYPE_INT, false, false, false, true);
		$this->quickInitVar('content_comments', XOBJ_DTYPE_INT);
		$this->quickInitVar('content_notification_sent', XOBJ_DTYPE_INT);

		$this->hideFieldFromForm('content_comments');
		$this->hideFieldFromForm('content_notification_sent');
		$this->hideFieldFromSingleView('content_comments');
		$this->hideFieldFromSingleView('content_notification_sent');

		$this->initCommonVar('counter', false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);

		$this->initNonPersistableVar('content_subs', XOBJ_DTYPE_INT);

		$this->setControl('content_body', 'dhtmltextarea');
		$this->setControl('content_uid', 'user');
		$this->setControl('content_status', array('itemHandler' => 'content', 'method' => 'getContent_statusArray', 'module' => 'content'));
		$this->setControl('content_visibility', array('itemHandler' => 'content', 'method' => 'getContent_visibleArray', 'module' => 'content'));
		$this->setControl('content_pid', array('itemHandler' => 'content', 'method' => 'getContentList', 'module' => 'content'));
		$this->setControl('categories', array('name' => 'categories', 'module' => 'imtagging'));
		$this->setControl('content_makesymlink', 'yesno');
		$this->setControl('content_showsubs', 'yesno');
		$this->setControl('content_cancomment', 'yesno');

		parent::initiateSEO();
	}

	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array('content_pid', 'content_uid', 'content_status', 'content_visibility', 'content_subs', 'content_tags'))) {
			return call_user_func(array($this, $key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * Retrieving the title of the parent page, linked to that
	 *
	 * @return str title of the parent content
	 */
	function content_pid() {
		static $content_pidArray;
		if (!is_array($content_pidArray)) {
			$content_pidArray = $this->handler->getContentList();
		}
		$ret = $this->getVar('content_pid', 'e');
		if ($ret > 0) {
			$ret = '<a href="' . $this->handler->_moduleUrl . $this->handler->_itemname . '.php?content_id=' . $ret . '">' . str_replace('-', '', $content_pidArray[$ret]) . '</a>';
		} else {
			$ret = $content_pidArray[$ret];
		}
		return $ret;
	}

	/**
	 * Retrieving the name of the author of the content, linked to his profile
	 *
	 * @return str name of the author of the content
	 */
	function content_uid() {
		return icms_member_user_Handler::getUserLink($this->getVar('content_uid', 'e'));
	}

	/**
	 * Retrieving the status of the content
	 *
	 * @param str status of the content
	 * @return mixed $content_statusArray[$ret] status of the content
	 */
	function content_status() {
		$ret = $this->getVar('content_status', 'e');
		$content_statusArray = $this->handler->getContent_statusArray();
		return $content_statusArray[$ret];
	}

	/**
	 * Retrieving the visibility of the content
	 *
	 * @return mixed $content_visibleArray[$ret] visibility of the content
	 */
	function content_visibility() {
		$ret = $this->getVar('content_visibility', 'e');
		$content_visibleArray = $this->handler->getContent_visibleArray();
		return $content_visibleArray[$ret];
	}

	function content_tags() {
		if ($this->getVar('content_tags', 'e') != '') {
			$tags = explode (',', $this->getVar('content_tags', 'e'));
			foreach ($tags as $k => $tag) {
				$tag = trim ($tag);
				$tag = ' <a href="' . $this->handler->_moduleUrl . 'index.php?tag=' . $tag . '">' . $tag . '</a>';
				$tags[$k] = $tag;
			}
			return implode(',', $tags);
		} else {
			return false;
		}
	}

	/**
	 * Retrieving the count of sub-pages of this page
	 *
	 * @return int number of sub-pages
	 */
	function content_subs() {
		$ret = $this->handler->getContentsSubsCount($this->getVar('content_id', 'e'));

		if ($ret > 0) {
			$ret = '<a href="' . $this->handler->_moduleUrl . 'admin/' . $this->handler->_itemname . '.php?content_pid=' . $this->getVar('content_id', 'e') . '">' . $ret . ' <img src="' . $this->handler->_moduleUrl . 'images/viewsubs.gif" align="absmiddle" /></a>';
		}
		return $ret;
	}

	function getReads() {
		return $this->getVar('counter');
	}

	function setReads($qtde = null) {
		$t = $this->getVar('counter');
		if (isset($qtde)) {
			$t += $qtde;
		} else {
			$t++;
		}
		$this->setVar('counter', $t);
	}

	/**
	 * Returns the need to br
	 *
	 * @return bool true | false
	 */
	function need_do_br() {
		global $icmsConfig;

		$content_module = icms_getModuleInfo('content');
		$groups = icms::$user->getGroups();

		if (file_exists(ICMS_EDITOR_PATH . "/" . $icmsConfig['editor_default'] . "/xoops_version.php") && icms::handler('icms_member_groupperm')->checkRight('use_wysiwygeditor', $content_module->getVar("mid"), $groups)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Check is user has access to view this content page
	 *
	 * User will be able to view the page if
	 *	- the status of the page is Published OR
	 *	- he is an admin OR
	 * 	  - he is the poster of this page
	 *
	 * @return bool true if user can view this page, false if not
	 */
	function accessGranted($perm_name = 0) {
		$gperm_handler = icms::handler('icms_member_groupperm');
		$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);

		$module = icms::handler('icms_module')->getByDirname(basename(dirname(__FILE__, 2)));

		$agroups = $gperm_handler->getGroupIds('module_admin', $module->getVar("mid"));
		$allowed_groups = array_intersect($groups, $agroups);

		$viewperm = $gperm_handler->checkRight('content_read', $this->getVar('content_id', 'e'), $groups, $module->getVar("mid"));

		if (is_object(icms::$user) && icms::$user->getVar("uid") == $this->getVar('content_uid', 'e')) {
			return true;
		}

		if ($viewperm && $this->getVar('content_status', 'e') == CONTENT_CONTENT_STATUS_PUBLISHED) {
			return true;
		}

		if ($viewperm && count($allowed_groups) > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Get the poster
	 *
	 * @param bool $link with link or not
	 * @return str poster name linked on his module poster page, or simply poster name
	 */
	function getPoster($link = false) {
		if (!$this->_poster_info) {
			$poster_uid = $this->getVar('content_uid', 'e');
			$userObj = icms::handler('icms_member')->getuser($poster_uid);

			/**
			 * We need to make sure the poster is a valid user object. It is possible the user no longer
			 * exists if, for example, he was previously deleted. In that case, we will return Anonymous
			 */
			if (is_object($userObj)) {
				$this->_poster_info['uid'] = $poster_uid;
				$this->_poster_info['uname'] = $userObj->getVar('uname');
				$this->_poster_info['link'] = '<a href="' . $this->handler->_moduleUrl . 'index.php?uid=' . $this->_poster_info['uid'] . '">' . $this->_poster_info['uname'] . '</a>';
			} else {
				global $icmsConfig;
				$this->_poster_info['uid'] = 0;
				$this->_poster_info['uname'] = $icmsConfig['anonymous'];
			}
		}
		if ($link && $this->_poster_info['uid']) {
			return $this->_poster_info['link'];
		} else {
			return $this->_poster_info['uname'];
		}
	}

	/**
	 * Retrieve content info (author and date)
	 *
	 * @return str content info
	 */
	function getContentInfo() {
		$ret = sprintf(_CO_CONTENT_CONTENT_INFO, $this->getPoster(true), $this->getVar('content_published_date'), $this->getVar('counter'));
		return $ret;
	}

	/**
	 * Check to see wether the current user can edit or delete this page
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $content_isAdmin;
		if (!is_object(icms::$user)) return false;
		if ($content_isAdmin) return true;
		return $this->getVar('content_uid', 'e') == icms::$user->getVar("uid");
	}

	function getPreviewItemLink() {
		$seo = $this->handler->makelink($this);
		$ret = '<a href="' . $this->handler->_moduleUrl . $this->handler->_itemname . '.php?content_id=' . $this->getVar('content_id', 'e') . '&amp;page=' . $seo . '" title="' . _AM_CONTENT_PREVIEW . '" target="_blank">' . $this->getVar('content_title') . '</a>';

		return $ret;
	}

	function getCloneItemLink() {
		$ret = '<a href="' . $this->handler->_moduleUrl . 'admin/' . $this->handler->_itemname . '.php?op=clone&amp;content_id=' . $this->getVar('content_id', 'e') . '" title="' . _AM_CONTENT_CONTENT_CLONE . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/editcopy.png" /></a>';

		return $ret;
	}

	function getViewItemLink($onlyUrl = false, $title = '', $target = '') {
		$ret = '<a href="' . $this->handler->_moduleUrl . 'admin/' . $this->handler->_itemname . '.php?op=view&amp;content_id=' . $this->getVar('content_id', 'e') . '" title="' . _AM_CONTENT_VIEW . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/viewmag.png" /></a>';

		return $ret;
	}

	function getContentSubs($toarray) {
		return $this->handler->getContentSubs($this->getVar('content_id', 'e'), $toarray);
	}

	function getContent_visibleControl() {
		$control = new icms_form_elements_Select('', 'content_visibility[]', $this->getVar('content_visibility', 'e'));
		$content_visibleArray = $this->handler->getContent_visibleArray();
		$control->addOptionArray($content_visibleArray);
		return $control->render();
	}

	function getContent_statusControl() {
		$control = new icms_form_elements_Select('', 'content_status[]', $this->getVar('content_status', 'e'));
		$content_statusArray = $this->handler->getContent_statusArray();
		$control->addOptionArray($content_statusArray);
		return $control->render();
	}

	/**
	 * Retrieve content comment info (number of comments)
	 *
	 * @return str content comment info
	 */
	function getCommentsInfo() {
		$content_comments = $this->getVar('content_comments');
		if ($content_comments) {
			return '<a href="' . $this->getItemLink(true) . '#comments_container">' . sprintf(_CO_CONTENT_CONTENT_COMMENTS_INFO, $content_comments) . '</a>';
		} else {
			return _CO_CONTENT_CONTENT_NO_COMMENT;
		}
	}

	/**
	 * Retrieve content lead, which is everything before the [more] tag
	 *
	 * @return str content lead
	 */
	function getContentLead() {
		$ret = $this->getVar('content_body');
		$ret = icms_core_DataFilter::icms_substr(icms_cleanTags($ret, array()), 0, 300);
		return $ret;
	}

	/**
	 * Sending the notification related to a content being published
	 *
	 * @return VOID
	 */
	function sendNotifContentPublished() {
		$module = icms::handler('icms_module')->getByDirname(basename(dirname(__FILE__, 2)));
		$tags ['CONTENT_TITLE'] = $this->getVar('content_title');
		$tags ['CONTENT_URL'] = $this->getItemLink(true);
		icms::handler('icms_data_notification')->triggerEvent('global', 0, 'content_published', $tags, array(), $module->getVar('mid'));
	}

	function getItemLink($onlyUrl = false) {
		$seo = $this->handler->makelink($this);
		$url = $this->handler->_moduleUrl . $this->handler->_itemname . '.php?content_id=' . $this->getVar('content_id') . '&amp;page=' . $seo;
		if ($onlyUrl) return $url;
		return '<a href="' . $url . '" title="">' . $this->getVar('content_title') . '</a>';
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of article info
	 */
	function toArray() {
		$ret = parent::toArray();

		$ret['content_info'] = $this->getContentInfo();
		$ret['content_lead'] = $this->getContentLead();
		$ret['content_comment_info'] = $this->getCommentsInfo();
		$ret['content_css'] = $this->getVar('content_css', 'e');
		$ret['content_subs'] = $this->getContentSubs($this->getVar('content_id', 'e'), true);
		$ret['content_hassubs'] = (count($ret['content_subs']) > 0) ? true : false;
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['content_posterid'] = $this->getVar('content_uid', 'e');
		$ret['itemLink'] = $this->getItemLink();
		//$ret['accessgranted'] = $this->accessGranted();

		/* Expose zone meta values so that the {zone} Smarty plugin can read them. */
		$ret['_meta'] = $this->loadMeta();

		return $ret;
	}

	// -----------------------------------------------------------------------
	// Zone meta methods
	// -----------------------------------------------------------------------

	/**
	 * Lazily load zone meta values from the database.
	 *
	 * @return array  name→value map (empty array for new objects)
	 */
	public function loadMeta() {
		if ($this->_meta !== null) {
			return $this->_meta;
		}

		$id = (int) $this->getVar('content_id', 'e');
		if ($id <= 0) {
			$this->_meta = array();
			return $this->_meta;
		}

		$metaHandler = icms_getModuleHandler('meta_field', 'content', 'content');
		$this->_meta = is_object($metaHandler)
			? $metaHandler->getMetaForItem('content', $id)
			: array();

		return $this->_meta;
	}

	/**
	 * Get a single zone meta value.
	 *
	 * @param  string $name  Zone name
	 * @return string        Stored value, or empty string if not found
	 */
	public function getMeta($name) {
		$meta = $this->loadMeta();
		return isset($meta[$name]) ? (string) $meta[$name] : '';
	}

	/**
	 * Set a single zone meta value (in-memory; persisted by ContentHandler::afterSave).
	 *
	 * @param string $name   Zone name
	 * @param string $value  Value to store
	 */
	public function setMeta($name, $value) {
		if ($this->_meta === null) {
			$this->loadMeta();
		}
		$this->_meta[$name] = (string) $value;
	}

	/**
	 * Build the admin edit-form, appending dynamic zone fields after the
	 * standard IPF fields.
	 *
	 * Zone fields are detected by scanning the content_default.html (or any
	 * other template that defines zones) with the ZoneScanner.
	 *
	 * @param  mixed  $action  Form title / action URL passed to parent
	 * @param  string $form    Form identifier
	 * @return object          ImpressCMS form object
	 */
	public function getForm($action = false, $form = 'addcontent') {
		$sform = parent::getForm($action, $form);

		$zones = $this->_detectZones();
		if (empty($zones)) {
			return $sform;
		}

		icms_loadLanguageFile('content', 'common');

		$meta = $this->loadMeta();

		/* Add a visual separator before zone fields. */
		$sform->addElement(new icms_form_elements_Label(
			defined('_CO_CONTENT_ZONE_SECTION') ? _CO_CONTENT_ZONE_SECTION : 'Template Zones',
			''
		));

		foreach ($zones as $zone) {
			$name    = $zone['name'];
			$type    = isset($zone['type'])    ? $zone['type']    : 'text';
			$label   = isset($zone['label'])   ? $zone['label']   : $name;
			$options = isset($zone['options']) ? $zone['options'] : '';
			$value   = isset($meta[$name])     ? $meta[$name]     : '';

			/* Field name in the HTML form – prefixed to avoid collisions. */
			$fieldName = 'zone_meta_' . $name;

			switch ($type) {
				case 'textarea':
					$element = new icms_form_elements_Textarea($label, $fieldName, $value, 5, 60);
					break;

				case 'select':
					$element = new icms_form_elements_Select($label, $fieldName, $value);
					foreach (explode(',', $options) as $opt) {
						$opt = trim($opt);
						if ($opt !== '') {
							$element->addOption($opt, $opt);
						}
					}
					break;

				case 'image':
					/* Store the image URL as plain text; a text field is sufficient. */
					$element = new icms_form_elements_Text($label, $fieldName, $value, 60, 255);
					break;

				case 'text':
				default:
					$element = new icms_form_elements_Text($label, $fieldName, $value, 60, 255);
					break;
			}

			$sform->addElement($element);
		}

		return $sform;
	}

	/**
	 * Scan templates for zone definitions.
	 *
	 * @return array[]  Zone definitions (may be empty)
	 */
	private function _detectZones() {
		if (!class_exists('mod_content_ZoneScanner')) {
			$scannerFile = dirname(__FILE__) . '/ZoneScanner.php';
			if (file_exists($scannerFile)) {
				require_once $scannerFile;
			}
		}
		if (!class_exists('mod_content_ZoneScanner')) {
			return array();
		}

		$scanner = new mod_content_ZoneScanner();
		return $scanner->scanTemplate('content_default.html');
	}
}
