//:interface to kitOffer
//:Please visit http://phpManufaktur.de for informations about kitIdea!
/**
 * kitOffer
 *
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2012
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */
// access to kitOffer
if (file_exists(WB_PATH.'/modules/kit_offer/class.frontend.php')) {
	require_once(WB_PATH.'/modules/kit_offer/class.frontend.php');
	$offer = new offerFrontend();
	$params = $offer->getParams();
	$params[offerFrontend::PARAM_PRESET] = (isset($preset)) ? (int) $preset : 1;
	$params[offerFrontend::PARAM_CSS] = (isset($css) && (strtolower($css) == 'false')) ? false : true;
	$params[offerFrontend::PARAM_JS] = (isset($js) && (strtolower($js) == 'false')) ? false : true;
	$params[offerFrontend::PARAM_SEARCH] = (isset($search) && (strtolower($search) == 'false')) ? false : true;
	$params[offerFrontend::PARAM_LANGUAGE] = (isset($language)) ? strtoupper($language) : LANGUAGE;
	$params[offerFrontend::PARAM_FALLBACK_LANGUAGE] = (isset($fallback_language)) ? strtoupper($fallback_language) : 'DE';
	$params[offerFrontend::PARAM_FALLBACK_PRESET] = (isset($fallback_preset)) ? (int) $fallback_preset : 1;
	$params[offerFrontend::PARAM_DEBUG] = (isset($debug) && (strtolower($debug) == 'true')) ? true : false;
	$params[offerFrontend::PARAM_ORDER_BY] = (isset($order_by)) ? strtolower($order_by) : 'last_change';
	$params[offerFrontend::PARAM_ID] = (isset($id)) ? (int) $id : -1;
	$params[offerFrontend::PARAM_VIEW] = (isset($view)) ? strtolower($view) : 'teaser';
	$params[offerFrontend::PARAM_LIMIT] = (isset($limit)) ? (int) $limit : -1;
	if (!$offer->setParams($params)) return $offer->getError();
	return $offer->action();
}
else {
	return "kitOffer is not installed!";
}