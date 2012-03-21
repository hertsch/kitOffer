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

require_once LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php';
require_once LEPTON_PATH.'/modules/droplets_extension/interface.php';

class offerFrontend {

  const REQUEST_ACTION = 'act';

  const ACTION_DEFAULT = 'def';
  const ACTION_DETAIL = 'det';
  const ACTION_ORDER = 'ord';

  const ANCHOR = 'ao';

  const ORDER_BY_LAST_CHANGE = 'last_change';
  const ORDER_BY_LAST_ID = 'id';

  const VIEW_TEASER = 'teaser';
  const VIEW_DETAIL = 'detail';
  const VIEW_ORDER = 'order';

  private $page_link = '';
  private $img_url = '';
  private $template_path = '';
  private $error = '';
  private $message = '';

  const PARAM_CSS = 'css';
  const PARAM_JS = 'js';
  const PARAM_PRESET = 'preset';
  const PARAM_SEARCH = 'search';
  const PARAM_LANGUAGE = 'language';
  const PARAM_FALLBACK_LANGUAGE = 'fallback_language';
  const PARAM_FALLBACK_PRESET = 'fallback_preset';
  const PARAM_DEBUG = 'debug';
  const PARAM_ORDER_BY = 'order_by';
  const PARAM_ID = 'id';
  const PARAM_VIEW = 'view';
  const PARAM_LIMIT = 'limit';

  private $params = array(
      self::PARAM_CSS => true,
      self::PARAM_JS => true,
      self::PARAM_PRESET => 1,
      self::PARAM_SEARCH => true,
      self::PARAM_LANGUAGE => KIT_OFFER_LANGUAGE,
      self::PARAM_FALLBACK_LANGUAGE => 'DE',
      self::PARAM_FALLBACK_PRESET => 1,
      self::PARAM_DEBUG => false,
      self::PARAM_ORDER_BY => self::ORDER_BY_LAST_CHANGE,
      self::PARAM_ID => -1,
      self::PARAM_VIEW => self::VIEW_TEASER,
      self::PARAM_LIMIT => -1
  );

  protected $lang = NULL;

  /**
   * Constructor for class offerFrontend
   */
  public function __construct() {
    global $lang;
    global $kitTools;
    $url = '';
    $_SESSION['FRONTEND'] = true;
    $kitTools->getPageLinkByPageID(PAGE_ID, $url);
    $this->page_link = $url;
    $this->template_path = LEPTON_PATH.'/modules/'. basename(dirname(__FILE__)).'/templates/frontend/';
    $this->img_url = LEPTON_URL.'/modules/'.basename(dirname(__FILE__)).'/images/';
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->lang = $lang;
  } // __construct()

  /**
   * Return the params available for the droplet [[kit_idea]] as array
   *
   * @return ARRAY $params
   */
  public function getParams() {
    return $this->params;
  } // getParams()

  /**
   * Set the params for the droplet {{kit_idea]]
   *
   * @param ARRAY $params
   * @return BOOL
   */
  public function setParams($params = array()) {
    $this->params = $params;
    // check only the preset path but not the subdirectories with the languages!
    if (!file_exists($this->template_path.$this->params[self::PARAM_PRESET])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->I18n("The preset directory <b>{{ directory }}</b> does not exists, can't load any template!", array(
          'directory' => '/modules/kit_offer/templates/frontend/'.$this->params[self::PARAM_PRESET].'/'))));
      return false;
    }
    return true;
  } // setParams()

  /**
   * Set $this->error to $error
   *
   * @param STR $error
   */
  public function setError($error) {
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
  public function clearError() {
    $this->error = '';
  }

  /**
   * Set $this->message to $message
   *
   * @param STR $message
   */
  public function setMessage($message) {
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
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.php');
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
   * Check if $this->message is empty
   *
   * @return BOOL
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage

  /**
   * Execute the desired template and return the completed template
   *
   * @param $template string - the filename of the template without path
   * @param $template_data array - the template data
   * @return string template or boolean false on error
   */
  protected function getTemplate($template, $template_data) {
    global $parser;
    $template_path = $this->template_path.$this->params[self::PARAM_PRESET].'/'.$this->params[self::PARAM_LANGUAGE].'/'.$template;
    if (!file_exists($template_path)) {
      // template does not exist - fallback to default language!
      $template_path = $this->template_path.$this->params[self::PARAM_PRESET].'/'.$this->params[self::PARAM_FALLBACK_LANGUAGE].'/'.$template;
      if (!file_exists($template_path)) {
        // template does not exists - fallback to the default preset!
        $template_path = $this->template_path.$this->params[self::PARAM_FALLBACK_PRESET].'/'.$this->params[self::PARAM_LANGUAGE].'/'.$template;
        if (!file_exists($template_path)) {
          // template does not exists - fallback to the default preset and the default language
          $template_path = $this->template_path.$this->params[self::PARAM_FALLBACK_PRESET].'/'.$this->params[self::PARAM_FALLBACK_LANGUAGE].'/'.$template;
          if (!file_exists($template_path)) {
            // template does not exists in any possible path - give up!
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->I18n(
                'Error: The template {{ template }} does not exists in any of the possible paths!',
                array('template' => $template))));
            return false;
          }
        }
      }
    }
    // add the template_path to the $template_data (for debugging purposes)
    if (!isset($template_data['template_path']))
      $template_data['template_path'] = $template_path;
    // add the debug flag to the $template_data
    if (!isset($template_data['DEBUG']))
      $template_data['DEBUG'] = (int) $this->params[self::PARAM_DEBUG];

    try {
      // try to execute the template with Dwoo
      $result = $parser->get($template_path, $template_data);
    }
    catch (Exception $e) {
      // prompt the Dwoo error
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->I18n(
          'Error executing template <b>{{ template }}</b>:<br />{{ error }}',
          array('template' => $template, 'error' => $e->getMessage()))));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Prevent XSS Cross Site Scripting
   *
   * @param refrence array $request
   * @return array $request
   */
  public function xssPrevent(&$request) {
    if (is_string($request)) {
      $request = html_entity_decode($request);
      $request = strip_tags($request);
      $request = trim($request);
      $request = stripslashes($request);
    }
    return $request;
  } // xssPrevent()

  /**
   * Action handler for offerFrontend
   *
   * @return STR result
   */
  public function action() {
    /**
     * to prevent cross site scripting XSS it is important to look also to
     * $_REQUESTs which are needed by other KIT addons. Addons which need
     * a $_REQUEST with HTML should set a key in $_SESSION['KIT_HTML_REQUEST']
     */
    $html_allowed = array();
    if (isset($_SESSION['KIT_HTML_REQUEST']))
      $html_allowed = $_SESSION['KIT_HTML_REQUEST'];
    $html = array();
    foreach ($html as $key)
      $html_allowed[] = $key;
    $_SESSION['KIT_HTML_REQUEST'] = $html_allowed;
    foreach ($_REQUEST as $key => $value) {
      if (!in_array($key, $html_allowed)) {
        $_REQUEST[$key] = $this->xssPrevent($value);
      }
    }

    $action = isset($_REQUEST[self::REQUEST_ACTION]) ? $_REQUEST[self::REQUEST_ACTION] : self::ACTION_DEFAULT;

    // load CSS?
    if ($this->params[self::PARAM_CSS]) {
      if (!is_registered_droplet_css('kit_offer', PAGE_ID))
        register_droplet_css('kit_offer', PAGE_ID, 'kit_offer', 'kit_offer.css');
    }
    elseif (is_registered_droplet_css('kit_offer', PAGE_ID)) {
      unregister_droplet_css('kit_offer', PAGE_ID);
    }

    // load Javascript?
    if ($this->params[self::PARAM_JS]) {
      if (!is_registered_droplet_js('kit_offer', PAGE_ID))
        register_droplet_js('kit_offer', PAGE_ID, 'kit_offer', 'kit_offer.js');
    }
    elseif (is_registered_droplet_js('kit_offer', PAGE_ID))
      unregister_droplet_js('kit_offer', PAGE_ID);

    switch ($action) :
    default :
      $content = $this->offerArticles();
    endswitch;

    $data = array(
        'LEPTON_URL' => LEPTON_URL,
        'IMG_URL' => $this->img_url,
        'error' => ($this->isError()) ? 1 : 0,
        'content' => ($this->isError()) ? $this->getError() : $content
        );
    return $this->getTemplate('body.lte', $data);
  } // action

  protected function unsanitizeText($text) {
    $text = stripcslashes($text);
    $text = str_replace("&#039;", "'", $text);
    $text = str_replace("&gt;", ">", $text);
    $text = str_replace("&quot;", "\"", $text);
    $text = str_replace("&lt;", "<", $text);
    return $text;
  }

  protected function offerArticles() {
    global $dbOfferArticles;
    global $kitTools;

    if ($this->params[self::PARAM_ID] > 0) {
      $SQL = sprintf("SELECT * FROM %s WHERE `%s`='%s' AND `%s`='%s'",
          $dbOfferArticles->getTableName(),
          dbOfferArticles::FIELD_STATUS,
          dbOfferArticles::STATUS_ACTIVE,
          dbOfferArticles::FIELD_ID,
          $this->params[self::PARAM_ID]);
    }
    else {
      $SQL = sprintf("SELECT * FROM %s WHERE `%s`='%s' ORDER BY `%s` DESC%s",
          $dbOfferArticles->getTableName(),
          dbOfferArticles::FIELD_STATUS,
          dbOfferArticles::STATUS_ACTIVE,
          ($this->params[self::PARAM_ORDER_BY] == self::ORDER_BY_LAST_CHANGE) ? dbOfferArticles::FIELD_TIMESTAMP : dbOfferArticles::FIELD_ID,
          ($this->params[self::PARAM_LIMIT] > 0) ? sprintf(" LIMIT %d", $this->params[self::PARAM_LIMIT]) : ''
          );
    }
    $articles = array();
    if (!$dbOfferArticles->sqlExec($SQL, $articles)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbOfferArticles->getError()));
      return false;
    }
    $article_array = array();
    foreach ($articles as $article) {
      foreach ($article as $key => $value) {
        switch ($key):
        case dbOfferArticles::FIELD_LONG_DESCRIPTION:
        case dbOfferArticles::FIELD_SHORT_DESCRIPTION:
          $article_array[$article[dbOfferArticles::FIELD_ID]][$key] = array(
              'name' => $key,
              'value' => $this->unsanitizeText($value)
          );
          break;
        case dbOfferArticles::FIELD_TIMESTAMP:
          $article_array[$article[dbOfferArticles::FIELD_ID]][$key] = array(
              'name' => $key,
              'value' => $value,
              'formatted' => date(CFG_DATETIME_STR, strtotime($value))
          );
          break;
        case dbOfferArticles::FIELD_PRICE:
          $article_array[$article[dbOfferArticles::FIELD_ID]][$key] = array(
              'name' => $key,
              'value' => $value,
              'formatted' => sprintf(CFG_CURRENCY, number_format($value, 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR))
          );
          break;
        case dbOfferArticles::FIELD_PRICE:
          $article_array[$article[dbOfferArticles::FIELD_ID]][$key] = array(
              'name' => $key,
              'value' => $value,
              'formatted' => number_format($value, 0, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR)
          );
          break;
        default:
          $article_array[$article[dbOfferArticles::FIELD_ID]][$key] = array(
              'name' => $key,
              'value' => $value
              );
          break;
        endswitch;
      }
      $article_array[$article[dbOfferArticles::FIELD_ID]]['action'] = array(
          'detail' => sprintf('%s%s%s#%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&',
              http_build_query(array(
                  self::REQUEST_ACTION => self::ACTION_DETAIL,
                  dbOfferArticles::FIELD_ID => $article[dbOfferArticles::FIELD_ID]
                  )),
              self::ANCHOR),
          'order' => sprintf('%s%s%s#%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&',
              http_build_query(array(
                  self::REQUEST_ACTION => self::ACTION_ORDER,
                  dbOfferArticles::FIELD_ID => $article[dbOfferArticles::FIELD_ID]
                  )),
              self::ANCHOR)
          );
    }

    $data = array(
        'articles' => $article_array
        );

    return $this->getTemplate('offer.teaser.lte', $data);
  } // offerDefault()

} // class offerFrontend