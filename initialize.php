<?php

/**
 * kitCronjob
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012 - phpManufaktur by Ralf Hertsch
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
if (!defined('LEPTON_PATH')) require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/wb2lepton.php';

if (!class_exists('manufaktur_I18n'))
  require_once LEPTON_PATH.'/modules/manufaktur_i18n/library.php';
global $lang;
if (!is_object($lang)) $lang = new manufaktur_I18n('kit_cronjob', LANGUAGE);

// load language depending onfiguration
if (!file_exists(LEPTON_PATH.'/modules/' . basename(dirname(__FILE__)) . '/languages/' . LANGUAGE . '.cfg.php')) {
  require_once(LEPTON_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.cfg.php');
  // important: language flag is used by template selection
  if (!defined('KIT_OFFER_LANGUAGE')) define('KIT_OFFER_LANGUAGE', 'DE');
}
else {
  require_once(LEPTON_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.cfg.php');
  if (!defined('KIT_OFFER_LANGUAGE')) define('KIT_OFFER_LANGUAGE', LANGUAGE);
}

if (!class_exists('Dwoo')) {
  require_once LEPTON_PATH.'/modules/dwoo/include.php';
}
// set cache and compile path for the template engine
$cache_path = LEPTON_PATH.'/temp/cache';
if (!file_exists($cache_path)) mkdir($cache_path, 0755, true);
$compiled_path = LEPTON_PATH.'/temp/compiled';
if (!file_exists($compiled_path)) mkdir($compiled_path, 0755, true);

// init the template engine
global $parser;
if (!is_object($parser)) $parser = new Dwoo($compiled_path, $cache_path);

// load extensions for the template engine
$loader = $parser->getLoader();
$loader->addDirectory(LEPTON_PATH.'/modules/kit_offer/templates/plugins/');

if (!class_exists('dbconnectle')) {
  require_once LEPTON_PATH.'/modules/dbconnect_le/include.php';
}

if (!class_exists('kitToolsLibrary')) {
	require_once WB_PATH.'/modules/kit_tools/class.tools.php';
}
global $kitTools;
if (!is_object($kitTools)) $kitTools = new kitToolsLibrary();

require_once LEPTON_PATH.'/modules/kit_offer/class.offer.php';

global $dbOfferArticles;
if (!is_object($dbOfferArticles)) $dbOfferArticles = new dbOfferArticles(true);