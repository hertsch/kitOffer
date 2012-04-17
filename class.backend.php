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
require_once LEPTON_PATH . '/modules/manufaktur_i18n/class.dialog.php';
require_once LEPTON_PATH.'/modules/manufaktur_config/class.dialog.php';

class offerBackend {

  const REQUEST_ACTION = 'act';
  const REQUEST_SUB_ACTION = 'sub';
  const REQUEST_ITEMS = 'its';

  const ACTION_ABOUT = 'abt';
  const ACTION_ARTICLES = 'art';
  const ACTION_CONFIG = 'cfg';
  const ACTION_DEFAULT = 'def';
  const ACTION_LANGUAGE = 'lng';
  const ACTION_SUB_ARTICLES = 'sar';
  const ACTION_SUB_ARTICLE_EDIT = 'sae';
  const ACTION_SUB_ARTICLE_CHECK = 'sac';
  const ACTION_SUB_GROUPS = 'sge';
  const ACTION_SUB_GROUPS_CHECK = 'sgc';

  private static $page_link = '';
  private static $img_url = '';
  private static $error = '';
  private static $message = '';

  protected $lang = NULL;

  private static $tab_navigation_array = array();
  private static $tab_articles_array = array();

  public function __construct() {
    global $lang;
    self::$page_link = ADMIN_URL . '/admintools/tool.php?tool=kit_offer';
    self::$img_url = LEPTON_URL . '/modules/' . basename(dirname(__FILE__)) . '/images/';
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->lang = $lang;
    // don't translate the Tab Strings here - this will be done in the template!
    self::$tab_navigation_array = array(
      self::ACTION_ARTICLES => $this->lang->I18n_Register('Articles'),
      self::ACTION_CONFIG => $this->lang->I18n_Register('Settings'),
      self::ACTION_LANGUAGE => $this->lang->I18n_Register('Languages'),
      self::ACTION_ABOUT => $this->lang->I18n_Register('About')
      );
    self::$tab_articles_array = array(
      self::ACTION_SUB_ARTICLES => $this->lang->I18n_Register('List'),
      self::ACTION_SUB_ARTICLE_EDIT => $this->lang->I18n_Register('Edit'),
      //self::ACTION_SUB_GROUPS => $this->lang->I18n_Register('Groups')
      );
  } // __construct()

  /**
   * Set $this->error to $error
   *
   * @param $error STR
   */
  protected function setError($error) {
    self::$error = $error;
  } // setError()

  /**
   * Get Error from $this->error;
   *
   * @return STR $this->error
   */
  public function getError() {
    return self::$error;
  } // getError()

  /**
   * Check if $this->error is empty
   *
   * @return BOOL
   */
  public function isError() {
    return (bool) !empty(self::$error);
  } // isError

  /**
   * Reset Error to empty String
   */
  protected function clearError() {
    self::$error = '';
  }

  /**
   * Set $this->message to $message
   *
   * @param $message STR
   */
  protected function setMessage($message) {
    self::$message = $message;
  } // setMessage()

  /**
   * Get Message from $this->message;
   *
   * @return STR $this->message
   */
  public function getMessage() {
    return self::$message;
  } // getMessage()

  /**
   * Check if $this->message is empty
   *
   * @return BOOL
   */
  public function isMessage() {
    return (bool) !empty(self::$message);
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
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->I18n(
          'Error executing the template ' . '<b>{{ template }}</b>: {{ error }}', array(
            'template' => basename($load_template),
            'error' => $e->getMessage()))));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param refrence array $request
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
   * @return string result dialog or message
   */
  public function action() {
    // to prevent cross site scripting is very important but look also to
    // $_REQUESTs which are needed by other KIT addons. Addons which need
    // a $_REQUEST with HTML should set a key in $_SESSION['KIT_HTML_REQUEST']
    $html_allowed = array(dbOfferArticles::FIELD_LONG_DESCRIPTION, dbOfferArticles::FIELD_SHORT_DESCRIPTION);
    if (!isset($_SESSION['KIT_HTML_REQUEST'])) $_SESSION['KIT_HTML_REQUEST'] = array();
    foreach ($html_allowed as $key)
      if (!in_array($key, $_SESSION['KIT_HTML_REQUEST'])) $_SESSION['KIT_HTML_REQUEST'][] = $key;
    foreach ($_REQUEST as $key => $value)
      if (!in_array($key, $_SESSION['KIT_HTML_REQUEST'])) $_REQUEST[$key] = $this->xssPrevent($value);

    $action = isset($_REQUEST[self::REQUEST_ACTION]) ? $_REQUEST[self::REQUEST_ACTION] : self::ACTION_DEFAULT;

    switch ($action) :
    case self::ACTION_ARTICLES:
      $this->show(self::ACTION_ARTICLES, $this->actionArticles());
      break;
    case self::ACTION_CONFIG:
      $this->show(self::ACTION_CONFIG, $this->dlgConfig());
      break;
    case self::ACTION_LANGUAGE:
      $this->show(self::ACTION_LANGUAGE, $this->dlgLanguage());
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
    foreach (self::$tab_navigation_array as $key => $value) {
      $navigation[] = array(
          'active' => ($key == $action) ? 1 : 0,
          'url' => sprintf('%s&%s', self::$page_link, http_build_query(array(
              self::REQUEST_ACTION => $key))),
          'text' => $value);
    }
    $data = array(
        'LEPTON_URL' => LEPTON_URL,
        'IMG_URL' => self::$img_url,
        'navigation' => $navigation,
        'error' => ($this->isError()) ? 1 : 0,
        'content' => ($this->isError()) ? $this->getError() : $content);
    echo $this->getTemplate('body.lte', $data);
  } // show()

  protected function showSubPage($action, $subaction, $content) {
    $navigation = array();
    foreach (self::$tab_articles_array as $key => $value) {
      $navigation[] = array(
          'active' => ($key == $subaction) ? 1 : 0,
          'url' => sprintf('%s&%s', self::$page_link, http_build_query(array(
              self::REQUEST_ACTION => $action,
              self::REQUEST_SUB_ACTION => $key))),
          'text' => $value
          );
    }
    $data = array(
        'navigation' => $navigation,
        'content' => $content,
        'IMG_URL' => self::$img_url
        );
    return $this->getTemplate('subpage.lte', $data);
  } // showSubPage()

  /**
   * execute the manufaktur_config dialog
   *
   * @return string configuration dialog for kitOffer
   */
  protected function dlgConfig() {
    $link = sprintf('%s&amp;%s',
        self::$page_link,
        http_build_query(array(self::REQUEST_ACTION => self::ACTION_CONFIG)));
    $dialog = new manufakturConfigDialog('kit_offer', 'kitOffer', $link);
    return $dialog->action();
  } // dlgConfig()

  /**
   * Execute the manufakturI18nDialog
   *
   * @return string manufakturI18nDialog for kitOffer
   */
  protected function dlgLanguage() {
    $link = sprintf('%s&amp;%s',
        self::$page_link,
        http_build_query(array(
            self::REQUEST_ACTION => self::ACTION_LANGUAGE)));
    $dialog = new manufakturI18nDialog('kit_offer', 'kitOffer', $link);
    return $dialog->action();
  } // dlgLanguage()

  /**
   * Information about kitIdea
   *
   * @return STR dialog
   */
  protected function dlgAbout() {
    $data = array(
        'version' => sprintf('%01.2f', $this->getVersion()),
        'img_url' => self::$img_url,
        'release_notes' => file_get_contents(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.txt'),);
    return $this->getTemplate('about.lte', $data);
  } // dlgAbout()

  protected function actionArticles() {

    $action = (isset($_REQUEST[self::REQUEST_SUB_ACTION])) ? $_REQUEST[self::REQUEST_SUB_ACTION] : self::ACTION_SUB_ARTICLES;

    switch ($action) {
      case self::ACTION_SUB_ARTICLES:
        $result = $this->showSubPage(self::ACTION_ARTICLES, self::ACTION_SUB_ARTICLES, $this->dlgArticlesList());
        break;
      case self::ACTION_SUB_ARTICLE_EDIT:
        $result = $this->showSubPage(self::ACTION_ARTICLES, self::ACTION_SUB_ARTICLE_EDIT, $this->dlgArticleEdit());
        break;
      case self::ACTION_SUB_ARTICLE_CHECK:
        $result = $this->showSubPage(self::ACTION_ARTICLES, self::ACTION_SUB_ARTICLE_EDIT, $this->checkArticleEdit());
        break;
      default:
        $result = $this->showSubPage(self::ACTION_ARTICLES, self::ACTION_SUB_ARTICLES, $this->dlgArticlesList());
        break;
    }
    return $result;
  } // actionArticles()

  protected function dlgArticlesList() {
    global $dbOfferArticles;

    $SQL = sprintf("SELECT * FROM %s WHERE `%s`!='%s' ORDER BY `%s` DESC",
        $dbOfferArticles->getTableName(),
        dbOfferArticles::FIELD_STATUS,
        dbOfferArticles::STATUS_DELETED,
        dbOfferArticles::FIELD_TIMESTAMP);
    $articles = array();
    if (!$dbOfferArticles->sqlExec($SQL, $articles)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbOfferArticles->getError()));
      return false;
    }
    $article_array = array();
    foreach ($articles as $article) {
      foreach ($article as $key => $value) {
        switch ($key):
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
        case dbOfferArticles::FIELD_IN_STOCK:
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
        endswitch;
      }
      // add the link for editing the article
      $article_array[$article[dbOfferArticles::FIELD_ID]]['edit'] = array(
          'url' => sprintf('%s&%s', self::$page_link, http_build_query(array(
              self::REQUEST_ACTION => self::ACTION_ARTICLES,
              self::REQUEST_SUB_ACTION => self::ACTION_SUB_ARTICLE_EDIT,
              dbOfferArticles::FIELD_ID => $article[dbOfferArticles::FIELD_ID]
              )))
          );
    }
    $data = array(
        'articles' => $article_array,
        );
    return $this->getTemplate('article.list.lte', $data);
  } // dlgArticlesList()

  /**
   * Dialog for creating and editing articles
   *
   * @return string dialog
   */
  protected function dlgArticleEdit() {
    global $dbOfferArticles;
    global $kitTools;

    $id = (isset($_REQUEST[dbOfferArticles::FIELD_ID])) ? $_REQUEST[dbOfferArticles::FIELD_ID] : -1;

    if ($id > 0) {
      $article = array();
      $SQL = sprintf("SELECT * FROM %s WHERE `%s`='%d'",
          $dbOfferArticles->getTableName(),
          dbOfferArticles::FIELD_ID,
          $id);
      if (!$dbOfferArticles->sqlExec($SQL, $article)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbOfferArticles->getError()));
        return false;
      }
      if (count($article) < 1) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
            $this->lang->I18n('The article with the <b>ID {{ id }}</b> does not exists!', array('id' => $id))));
        return false;
      }
      $article = $article[0];
    }
    else {
      $article = $dbOfferArticles->getFields();
      $article[dbOfferArticles::FIELD_PRICE] = number_format(0, 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR);
      $article[dbOfferArticles::FIELD_IN_STOCK] = -1;
      $article[dbOfferArticles::FIELD_ID] = $id;
    }

    foreach ($dbOfferArticles->getFields() as $key => $value) {
      if (isset($_REQUEST[$key])) {
        switch ($key) {
          case dbOfferArticles::FIELD_PRICE:
            $article[$key] = $kitTools->str2float($_REQUEST[$key], CFG_THOUSAND_SEPARATOR, CFG_DECIMAL_SEPARATOR);
            break;
          default:
            $article[$key] = $_REQUEST[$key];
            break;
        }
      }
    }

    $article_array = array();
    foreach ($article as $key => $value) {
      switch ($key) {
        case dbOfferArticles::FIELD_LONG_DESCRIPTION:
        case dbOfferArticles::FIELD_SHORT_DESCRIPTION:
          $article_array[$key] = array(
              'name' => $key,
              'value' => $this->unsanitizeText($value)
          );
          break;
        case dbOfferArticles::FIELD_PRICE:
          $article_array[$key] = array(
            'name' => $key,
            'value' => $value,
            'formatted' => sprintf(CFG_CURRENCY, number_format($value, 2, CFG_DECIMAL_SEPARATOR, CFG_THOUSAND_SEPARATOR))
          );
          break;
        default:
          $article_array[$key] = array(
            'name' => $key,
            'value' => $value
          );
          break;
      }
    }

    $data = array(
        'form' => array(
            'name' => 'article_edit',
            'action' => $this->page_link
            ),
        'action' => array(
            'name' => self::REQUEST_ACTION,
            'value' => self::ACTION_ARTICLES
            ),
        'sub_action' => array(
            'name' => self::REQUEST_SUB_ACTION,
            'value' => self::ACTION_SUB_ARTICLE_CHECK,
            ),
        'message' => array(
            'active' => (int) $this->isMessage(),
            'text' => $this->isMessage() ? $this->getMessage() : ''
            ),
        'fields' => $article_array,
        'IMG_URL' => $this->img_url
        );
    return $this->getTemplate('article.edit.lte', $data);
  } // dlgArticleEdit()

  protected function sanitizeVariable($item) {
    if (!is_array($item)) {
      // undoing 'magic_quotes_gpc = On' directive
      if (get_magic_quotes_gpc())
        $item = stripcslashes($item);
      $item = $this->sanitizeText($item);
    }
    return $item;
  }

  // does the actual 'html' and 'sql' sanitization. customize if you want.
  protected function sanitizeText($text) {
    $text = str_replace("<", "&lt;", $text);
    $text = str_replace(">", "&gt;", $text);
    $text = str_replace("\"", "&quot;", $text);
    $text = str_replace("'", "&#039;", $text);
    // it is recommended to replace 'addslashes' with 'mysql_real_escape_string' or whatever db specific fucntion used for escaping. However 'mysql_real_escape_string' is slower because it has to connect to mysql.
    $text = mysql_real_escape_string($text);
    return $text;
  }

  protected function unsanitizeText($text) {
    $text =  stripcslashes($text);
    $text = str_replace("&#039;", "'", $text);
    $text = str_replace("&gt;", ">", $text);
    $text = str_replace("&quot;", "\"", $text);
    $text = str_replace("&lt;", "<", $text);
    return $text;
  }

  /**
   * Check a new or changed article and save it
   *
   * @return string dlgArticleEdit()
   */
  protected function checkArticleEdit() {
    global $dbOfferArticles;
    global $kitTools;

    $id = isset($_REQUEST[dbOfferArticles::FIELD_ID]) ? $_REQUEST[dbOfferArticles::FIELD_ID] : -1;
    $checked = true;
    $article = array();
    $message = '';
    foreach ($dbOfferArticles->getFields() as $key => $value) {
      switch ($key):
      case dbOfferArticles::FIELD_NUMBER:
        // if no article number is set, use the ID
        $article[$key] = (isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) ? $_REQUEST[$key] : $id;
        break;
      case dbOfferArticles::FIELD_LONG_DESCRIPTION:
      case dbOfferArticles::FIELD_SHORT_DESCRIPTION:
        if (!isset($_REQUEST[$key]) || empty($_REQUEST[$key])) {
          $checked = false;
          $field = $this->lang->I18n($key);
          $message .= $this->lang->I18n('<p>The field <b>{{ field }}</b> must contain a value!</p>', array('field' => $field));
        }
        else {
          $article[$key] = $this->sanitizeVariable($_REQUEST[$key]);
        }
        break;
      case dbOfferArticles::FIELD_NAME:
        if (!isset($_REQUEST[$key]) || empty($_REQUEST[$key])) {
          $checked = false;
          $field = $this->lang->I18n($key);
          $message .= $this->lang->I18n('<p>The field <b>{{ field }}</b> must contain a value!</p>', array('field' => $field));
        }
        else {
          $article[$key] = trim($_REQUEST[$key]);
        }
        break;
      case dbOfferArticles::FIELD_PRICE:
        if (!isset($_REQUEST[$key])) {
          $checked = false;
          $field = $this->lang->I18n($key);
          $message .= $this->lang->I18n('<p>The field <b>{{ field }}</b> must contain a value!</p>', array('field' => $field));
        }
        else {
          // convert to float
          $article[$key] = $kitTools->str2float($_REQUEST[$key], CFG_THOUSAND_SEPARATOR, CFG_DECIMAL_SEPARATOR);
        }
        break;
      case dbOfferArticles::FIELD_IN_STOCK:
        if (!isset($_REQUEST[$key])) {
          $checked = false;
          $field = $this->lang->I18n($key);
          $message .= $this->lang->I18n('<p>The field <b>{{ field }}</b> must contain a value!</p>', array('field' => $field));
        }
        else {
          // convert to integer
          $article[$key] = $kitTools->str2int($_REQUEST[$key], CFG_THOUSAND_SEPARATOR, CFG_DECIMAL_SEPARATOR);
        }
        break;
      case dbOfferArticles::FIELD_STATUS:
      case dbOfferArticles::FIELD_ID:
      case dbOfferArticles::FIELD_IMAGES:
      case dbOfferArticles::FIELD_GROUP:
      default:
        // nothing to do...
        break;
      endswitch;
    }

    if ($checked) {
      if ($id > 0) {
        // update existing article
        $where = array(
            dbOfferArticles::FIELD_ID => $id
            );
        if (!$dbOfferArticles->sqlUpdateRecord($article, $where)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbOfferArticles->getError()));
          return false;
        }
        $message .= $this->lang->I18n('<p>The article with the ID {{ id }} was successfull updated.</p>', array('id' => $id));
      }
      else {
        // ad a new article
        if (!$dbOfferArticles->sqlInsertRecord($article, $id)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbOfferArticles->getError()));
          return false;
        }
        if (($article[dbOfferArticles::FIELD_NUMBER] == -1) || empty($article[dbOfferArticles::FIELD_NUMBER])) {
          // set article number to article ID
          $article_number = sprintf('%05d', $id);
          $where = array(
              dbOfferArticles::FIELD_ID => $id
              );
          $data = array(
              dbOfferArticles::FIELD_NUMBER => $article_number
              );
          if (!$dbOfferArticles->sqlUpdateRecord($data, $where)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbOfferArticles->getError()));
            return false;
          }
          $message .= $this->lang->I18n('<p>kitOffer has changed the article number to {{ number }}.</p>', array('number' => $article_number));
        }
        $message .= $this->lang->I18n('<p>The article with the ID {{ id }} was successfull added.</p>', array('id' => $id));
      }
      // unset $_REQUEST's
      foreach ($dbOfferArticles->getFields() as $key => $value) {
        unset($_REQUEST[$key]);
      }
      // set ID
      $_REQUEST[dbOfferArticles::FIELD_ID] = $id;
    }
    $this->setMessage($message);
    return $this->dlgArticleEdit();
  } // checkArticleEdit()

} // class offerBackend