<?php

/**
 * kitOffer
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012 phpManufaktur by Ralf Hertsch
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License (GPL)
 * @version $Id$
 *
 * FOR VERSION- AND RELEASE NOTES PLEASE LOOK AT INFO.TXT!
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
    if (defined('LEPTON_VERSION')) include(WB_PATH.'/framework/class.secure.php');
} else {
    $oneback = "../";
    $root = $oneback;
    $level = 1;
    while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
        $root .= $oneback;
        $level += 1;
    }
    if (file_exists($root.'/framework/class.secure.php')) {
        include($root.'/framework/class.secure.php');
    } else {
        trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!",
                $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
    }
}
// end include class.secure.php

// wb2lepton compatibility
if (!defined('LEPTON_PATH'))
  require_once WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/wb2lepton.php';

require_once(LEPTON_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php');

/**
 * This Plugin insert the LEPTON standard WYSIWYG editor. If possible, you can overwrite
 * the WYSIWYG Admin settings and select a own toolbar and skin
 *
 * @param Dwoo $dwoo
 * @param string $name - the name for the editor
 * @param string $id - ID for the editor
 * @param string $content
 * @param string $width - CSS value, allowed are %, em and px
 * @param string $height - CSS value, allowed are %, em and px
 * @param string $toolbar - allowed values: Full, Smart, Simple
 * @param string $skin - allowed values: cirkuit, default, o2k7
 * @return string WYSIWYG editor
 */
function Dwoo_Plugin_wysiwyg_editor(Dwoo $dwoo, $name, $id, $content, $width='100%', $height='350px', $toolbar=null, $skin='default') {
  global $database;
  $old_settings = null;
  if (!is_null($toolbar) && file_exists(LEPTON_PATH.'/modules/wysiwyg_admin/tool.php')) {
    // use WYSIWYG Admin to set a own toolbar and skin
    $skin = strtolower($skin);
    if (in_array($skin, array('cirkuit','default','o2k7')) && in_array($toolbar, array('Full','Smart','Simple'))) {
      $SQL = sprintf("SELECT * FROM %smod_wysiwyg_admin WHERE `editor`='%s'", TABLE_PREFIX, WYSIWYG_EDITOR);
      $query = $database->query($SQL);
      if ($database->is_error()) {
        return sprintf('[Dwoo_Plugin_wysiwyg_editor() - %s] %s', __LINE__, $database->get_error());
      }
      if ($query->numRows() > 0) {
        $old_settings = $query->fetchRow(MYSQL_ASSOC);
        $SQL = sprintf("UPDATE %smod_wysiwyg_admin SET `skin`='%s', `menu`='%s', `width`='%s', `height`='%s' WHERE `id`='%d'",
            TABLE_PREFIX, $skin, $toolbar, $width, $height, $old_settings['id']);
        $database->query($SQL);
        if ($database->is_error()) {
          return sprintf('[Dwoo_Plugin_wysiwyg_editor() - %s] %s', __LINE__, $database->get_error());
        }
      }
    }
  }
  ob_start();
    show_wysiwyg_editor($name, $id, $content, $width, $height);
    $editor = ob_get_contents();
  ob_end_clean();
  if (!is_null($old_settings)) {
    // reset the WYSIWYG Admin settings
    $SQL = sprintf("UPDATE %smod_wysiwyg_admin SET `skin`='%s', `menu`='%s', `width`='%s', `height`='%s' WHERE `id`='%d'",
        TABLE_PREFIX, $old_settings['skin'], $old_settings['menu'], $old_settings['width'], $old_settings['height'], $old_settings['id']);
    $database->query($SQL);
    if ($database->is_error()) {
      return sprintf('[Dwoo_Plugin_wysiwyg_editor() - %s] %s', __LINE__, $database->get_error());
    }
  }
  return $editor;
} // Dwoo_Plugin_wysiwyg_editor()