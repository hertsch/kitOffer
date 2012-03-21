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
  if (defined('LEPTON_VERSION'))
    include(WB_PATH . '/framework/class.secure.php');
} else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root . '/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root . '/framework/class.secure.php')) {
    include($root . '/framework/class.secure.php');
  } else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

// wb2lepton compatibility
if (!defined('LEPTON_PATH'))
  require_once WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/wb2lepton.php';

if (!class_exists('dbconnectle')) {
  require_once LEPTON_PATH.'/modules/dbconnect_le/include.php';
}

class dbOfferArticles extends dbConnectLE {

  const FIELD_ID = 'article_id';
  const FIELD_NUMBER = 'article_number';
  const FIELD_NAME = 'article_name';
  const FIELD_GROUP = 'article_group';
  const FIELD_SHORT_DESCRIPTION = 'article_short_description';
  const FIELD_LONG_DESCRIPTION = 'article_long_description';
  const FIELD_IMAGES = 'article_images';
  const FIELD_PRICE = 'article_price';
  const FIELD_IN_STOCK = 'article_in_stock';
  const FIELD_STATUS = 'article_status';
  const FIELD_TIMESTAMP = 'article_timestamp';

  const STATUS_ACTIVE = 'ACTIVE';
  const STATUS_LOCKED = 'LOCKED';
  const STATUS_DELETED = 'DELETED';

  private $createTable = false;

  /**
   * Constructor
   *
   * @param $createTable boolean
   */
  public function __construct($createTable = false) {
    $this->createTable = $createTable;
    parent::__construct();
    $this->setTableName('mod_kit_offer_articles');
    $this->addFieldDefinition(self::FIELD_ID, "INT(11) NOT NULL AUTO_INCREMENT", true);
    $this->addFieldDefinition(self::FIELD_NUMBER, "VARCHAR(64) NOT NULL DEFAULT ''");
    $this->addFieldDefinition(self::FIELD_NAME, "VARCHAR(64) NOT NULL DEFAULT ''");
    $this->addFieldDefinition(self::FIELD_GROUP, "INT(11) NOT NULL DEFAULT '-1'");
    $this->addFieldDefinition(self::FIELD_SHORT_DESCRIPTION, "TEXT", false, false, true);
    $this->addFieldDefinition(self::FIELD_LONG_DESCRIPTION, "TEXT", false, false, true);
    $this->addFieldDefinition(self::FIELD_IMAGES, "VARCHAR(255) NOT NULL DEFAULT ''");
    $this->addFieldDefinition(self::FIELD_PRICE, "FLOAT(11) NOT NULL DEFAULT '0'");
    $this->addFieldDefinition(self::FIELD_IN_STOCK, "INT(11) NOT NULL DEFAULT '-1'");
    $this->addFieldDefinition(self::FIELD_STATUS, "ENUM('ACTIVE','LOCKED','DELETED') NOT NULL DEFAULT 'ACTIVE'");
    $this->addFieldDefinition(self::FIELD_TIMESTAMP, "TIMESTAMP");
    $this->setIndexFields(array(self::FIELD_NAME));
    //$this->setAllowedHTMLtags('<a><b><br><div><em><i><p><span><strong><img><li><ul>');
    $this->checkFieldDefinitions();
    // Tabelle erstellen
    if ($this->createTable) {
      if (!$this->sqlTableExists()) {
        if (!$this->sqlCreateTable()) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
        }
      }
    }
    // set timezone
    date_default_timezone_set(CFG_TIME_ZONE);
  } // __construct()

} // class dbOfferArticle