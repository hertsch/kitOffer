<?php

/**
 * kitOffer
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
if (!defined('LEPTON_PATH'))
  require_once WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/wb2lepton.php';

class offerBackend {

  const REQUEST_ACTION = 'act';
  const REQUEST_ITEMS = 'its';

  const ACTION_ABOUT = 'abt';
  const ACTION_CONFIG = 'cfg';
  const ACTION_CONFIG_CHECK = 'cfgc';
  const ACTION_DEFAULT = 'def';

  private $page_link = '';
  private $img_url = '';
  private $template_path = '';
  private $error = '';
  private $message = '';

  protected $lang = NULL;
  protected $tab_navigation_array = null;

  public function __construct() {
    global $lang;
    $this->page_link = ADMIN_URL . '/admintools/tool.php?tool=kit_offer';
    $this->img_url = LEPTON_URL . '/modules/' . basename(dirname(__FILE__)) . '/images/';
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->lang = $lang;
    // don't translate the Tab Strings here - this will be done in the template!
    $this->tab_navigation_array = array(
        self::ACTION_CONFIG => 'Settings',
        self::ACTION_LANGUAGE => 'Languages',
        self::ACTION_ABOUT => 'About');
  } // __construct()

  /**
   * Set $this->error to $error
   *
   * @param $error STR
   */
  protected function setError($error) {
    $this->error = $error;
  } // setError()

  /**
   * Get Error from $this->error;
   *
   * @return STR $this->error
   */
  public function getError() {
    return $this->error;
  } // getError()

  /**
   * Check if $this->error is empty
   *
   * @return BOOL
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Reset Error to empty String
   */
  protected function clearError() {
    $this->error = '';
  }

  /**
   * Set $this->message to $message
   *
   * @param $message STR
   */
  protected function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
   * Get Message from $this->message;
   *
   * @return STR $this->message
   */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
   * Check if $this->message is empty
   *
   * @return BOOL
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage

  /**
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.php');
    if ($info_text == false) {
      return -1;
    }
    // walk through array
    foreach ($info_text as $item) {
      if (strpos($item, '$module_version') !== false) {
        // split string $module_version
        $value = explode('=', $item);
        // return floatval
        return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
      }
    }
    return -1;
  } // getVersion()

  /**
   * Return the needed template
   *
   * @param $template string
   * @param $template_data array
   */
  protected function getTemplate($template, $template_data) {
    global $parser;

    $template_path = LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/templates/backend/';

    // check if a custom template exists ...
    $load_template = (file_exists($template_path . 'custom.' . $template)) ? $template_path . 'custom.' . $template
    : $template_path . $template;
    try {
      $result = $parser->get($load_template, $template_data);
    } catch (Exception $e) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->I18n('Error executing the template ' . '<b>{{ template }}</b>: {{ error }}', array(
          'template' => basename($load_template),
          'error' => $e->getMessage()))));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param $_REQUEST REFERENCE
   *          Array
   * @return $request
   */
  protected function xssPrevent(&$request) {
    if (is_string($request)) {
      $request = html_entity_decode($request);
      $request = strip_tags($request);
      $request = trim($request);
      $request = stripslashes($request);
    }
    return $request;
  } // xssPrevent()

  /**
   * Action handler of the class
   *
   * @return STR result dialog or message
   */
  public function action() {
    $html_allowed = array();
    foreach ($_REQUEST as $key => $value) {
      if (strpos($key, 'cfg_') == 0)
        continue;
      // ignore config values!
      if (!in_array($key, $html_allowed)) {
        $_REQUEST[$key] = $this->xssPrevent($value);
      }
    }

    $action = isset($_REQUEST[self::REQUEST_ACTION]) ? $_REQUEST[self::REQUEST_ACTION] : self::ACTION_DEFAULT;
    if (($action == self::ACTION_DEFAULT) && isset($_REQUEST[I18n_Dialog::REQUEST_ACTION]))
      $action = self::ACTION_LANGUAGE;

    switch ($action) :
    case self::ACTION_CONFIG:
      $this->show(self::ACTION_CONFIG, $this->dlgConfig());
    break;
    case self::ACTION_CONFIG_CHECK:
      $this->show(self::ACTION_CONFIG, $this->checkConfig());
      break;
    case self::ACTION_ABOUT:
      $this->show(self::ACTION_ABOUT, $this->dlgAbout());
      break;
    default:
      $this->show(self::ACTION_ABOUT, $this->dlgAbout());
      break;
    endswitch;
  } // action

  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   *
   * @param STR $action - aktives Navigationselement
   * @param STR $content - Inhalt
   *
   * @return ECHO RESULT
   */
  protected function show($action, $content) {
    $navigation = array();
    foreach ($this->tab_navigation_array as $key => $value) {
      $navigation[] = array(
          'active' => ($key == $action) ? 1 : 0,
          'url' => sprintf('%s&%s', $this->page_link, http_build_query(array(
              self::REQUEST_ACTION => $key))),
          'text' => $value);
    }
    $data = array(
        'LEPTON_URL' => LEPTON_URL,
        'IMG_URL' => $this->img_url,
        'navigation' => $navigation,
        'error' => ($this->isError()) ? 1 : 0,
        'content' => ($this->isError()) ? $this->getError() : $content);
    echo $this->getTemplate('body.lte', $data);
  } // show()


  protected function dlgConfig() {
    return __METHOD__;
  } // dlgConfig()

  /**
   * Information about kitIdea
   *
   * @return STR dialog
   */
  protected function dlgAbout() {
    $data = array(
        'version' => sprintf('%01.2f', $this->getVersion()),
        'img_url' => $this->img_url,
        'release_notes' => file_get_contents(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.txt'),);
    return $this->getTemplate('about.lte', $data);
  } // dlgAbout()


} // class offerBackend