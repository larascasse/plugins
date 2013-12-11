<?php
/**
*	iAdvize
*-----------------------------------------------------------------------------------------
*   @version	$version 1.0.0 iAdvize 2013-08-06
*   @copyright	Copyright (C) 2013 iAdvize. Tous droits réservés / All rights reserved.
*/
/**
*   Displays 	<a href="http://www.gnu.org/licenses/gpl-2.0.html">GNU/GPL License</a>
*	@license	GNU General Public License version 2 or later; see LICENSE.txt
*/
/**
*	@author		iadvize <jerome.regoin@iadvize.com>
*/
/**
*	@link		http://www.iadvize.com
*-----------------------------------------------------------------------------------------
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemIadvize extends JPlugin {
	public function onAfterInitialise() {
		self::idzGetiAdvizeID();
	}

/**
 * after page render, create the iAdvize full tag
 *
 */
	public function onAfterRender() {
		$script = '';
		$commerce = self::idzCheckCommerce();
		if (isset($commerce) && $commerce) {
			//if cart checkout success
			$page = self::idzCheckPage($commerce);
			if (isset($page) && $page == true) {
				$script .= self::idzAddConversion($commerce);
			}
			$script .= self::idzAddCustomData($commerce);
		}
			$script .= self::idzAddTracking();
			self::idzAddTag($script);
	}

/**
 * verify if the iAdvize Id is empty
 * get api code to return iAdvize ID
 * Set the iAdvize ID to database
 */
	private function idzGetiAdvizeID() {
		$iAdvizeId = '';
		$iAdvizeId = htmlspecialchars($this->params->get('idiAdvize'));
		if (!isset($iAdvizeId) || empty($iAdvizeId)) {
			$root = JURI::root(true);
			if (!isset($root) || empty($root)) {
				$hostname = JURI::root();
			}
			else {
				$hostname = str_replace(JURI::root(true).'/', '', JURI::root());
			}
			//get back iAdvize Id
			$out = 'wp';
			// $out = 'joomla';
			// if ($commerce = idzCheckCommerce()) {
			// 	$out .= '/'.$commerce;
			// }
			$url = 'http://www.iadvize.com/api/getcode.php?&out='.$out.
			'&url=' . str_replace("http://", "", htmlspecialchars($hostname));
			$iAdvizeId = file_get_contents($url);

			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__extensions'));
			$defaults = '{"idiAdvize":"'.$iAdvizeId.'"}'; // JSON format for the parameters
			$query->set($db->quoteName('params') . ' = ' . $db->quote($defaults));
			$query->where($db->quoteName('name') . ' = ' . $db->quote('PLG_SYSTEM_IADVIZE'));
			$db->setQuery($query);
			$db->query();
		}
	}

/**
 *
 * @return string : idz tag for tracking
 */
	private function idzAddTracking() {
		$iAdvizeId = htmlspecialchars($this->params->get('idiAdvize'));
		$script = "<!-- START IADVIZE LIVECHAT -->\n
			<script type='text/javascript'>\n
				(function() {
				var idz = document.createElement('script'); idz.type = 'text/javascript'; idz.async = true;\n
				idz.src = document.location.protocol + \"//lc.iadvize.com/iadvize.js?sid=".$iAdvizeId."\";\n
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(idz, s);\n
				})();\n
			</script>\n
		<!-- END IADVIZE LIVECHAT -->\n";
		return $script;
	}

/**
 * get cart amount, cart count, current page and the external ID
 * @return string : idz tag for custom data
 */
	private function idzAddCustomData($commerce) {
		$cartAmount = 0;
		$cat = ' ';
		$type = ' ';
		$cartcount = 0;
		$user = JFactory::getUser();
		$extId = $user->id;
		$page = JURI::current();
		// $db = JFactory::getDbo();
		// $query = $db->getQuery(true);
		$myabsoluteurl=JURI::base();
		$currentPage = str_replace($myabsoluteurl, "", $page);
		$currentPage = explode('/', $currentPage);
		if (isset($currentPage[3]) && !empty($currentPage[3])) {
			$cat = $currentPage[3];
		}
		if (isset($currentPage[4]) && !empty($currentPage[4])) {
			$type = $currentPage[4];
		}
		if (!strstr($page, 'administrator') && $commerce == 'virtuemart') {
			$cart = VirtueMartCart::getCart();
			$cart2 = $cart->pricesUnformatted;
			$cartAmount = ($cart2['billTotal']);
			$cartcount = 0;
			foreach ($cart->products as $products) {
				$quantity = get_object_vars($products);
				$count = $quantity['quantity'];
				$cartcount = $cartcount + $count;
			}
		} elseif (!strstr($page, 'administrator') && $commerce == 'hikashop') {
			$cartcount = 0;
			$cartAmount = 0;
			$class = hikashop::get('class.cart');
			$rows = $class->get();
			if ($rows) {
				$carthika = new hikashopCartClass();
				$cart = $carthika->loadFullCart();
				$cartAmount = $cart->payment;
				$cartAmount = $cartAmount->total;
				$cartAmount = $cartAmount->prices[0];
				$cartAmount = $cartAmount->price_value;
				$products = $cart->products;
				foreach ($products as $product) {
					$cartcount += $product->cart_product_quantity;
				}
			}
		} elseif (!strstr($page, 'administrator') && $commerce == 'joomshopping') {
			if (isset($_REQUEST['controller']) && !empty($_REQUEST['controller'])) {
				$cat = $_REQUEST['controller'];
			}
			if (isset($_REQUEST['task']) && !empty($_REQUEST['task'])) {
				$type = $_REQUEST['task'];
			}
			$cart = JModel::getInstance('cart', 'jshop');
			if ($cart) {
				$cart->load();
				$cartcount = $cart->count_product;
				$cartAmount = $cart->price_product;
			}
		} elseif (!strstr($page, 'administrator') && $commerce == 'tienda') {
			$cat = ($_REQUEST['view']);
			$type_de_page = ($_REQUEST['task']);
			Tienda::load( 'TiendaHelperCarts', 'helpers.carts' );
			$items = TiendaHelperCarts::getProductsInfo();
			$cartcount = 0 ;
			$cartcAmount = 0;
			foreach ($items as $item) {
				$cartcount += $item->orderitem_quantity;
				$cartAmount += $item->orderitem_price * $item->orderitem_quantity;
			}
		} elseif (!strstr($page, 'administrator') && $commerce == 'redshop') {
			$session = ($_SESSION['__default']['cart']);
			$cartAmount = $_SESSION['__default']['cart']['total'];
			$type = $_REQUEST['view'];
			if (isset($_REQUEST['layout']) && !empty($_REQUEST['layout'])) {
				$cat = $_REQUEST['layout'];
			}
			for ($i=0; $i < $session['idx']; $i++) {
				$cartcount += $session[$i]['quantity'];
			}
		} elseif (!strstr($page, 'administrator') && $commerce == 'j2store') {
			$type = $_REQUEST['view'];
			if (isset($_REQUEST['layout']) && !empty($_REQUEST['layout'])) {
				$cat = $_REQUEST['layout'];
			}
			$cart = new J2StoreHelperCart();
			$cartcount = $cart->countProducts();
			$cartAmount = $cart->getTotal();
		} elseif (!strstr($page, 'administrator') && $commerce == 'mijoshop') {
			$type = $_REQUEST['view'];
			if (isset($_REQUEST['layout']) && !empty($_REQUEST['layout'])) {
				$cat = $_REQUEST['layout'];
			} elseif (isset($_REQUEST['route']) && !empty($_REQUEST['route'])) {
				$pages = explode('/', $_REQUEST['route']);
				$type = $pages[0];
				$cat = $pages [1];
			}
			$registry = $GLOBALS['registry'];
			$cart = new Cart($registry);
			$cartAmount = $cart->getTotal();
			$cartcount = $cart->countProducts();
		}
		$script = "<!-- START IADVIZE CUSTOM DATA -->\n
			<script type=\"text/javascript\">
				var idzCustomData = {
				\"extId\":\"$extId\",
				\"cartamount\": $cartAmount,
				\"categorie_page\":\"$cat\",
				\"type_de_page\":\"$type\",
				\"cartcount\":$cartcount
				};
			</script>\n
			<!-- END IADVIZE CUSTOM DATA -->\n";
		return $script;
	}

/**
 * get the cart amount and the order id after order success
 * @return string : idz tag for tracking conversion
 */
	private function idzAddConversion($commerce) {
		$cartAmount = '';
		$order_id = '';
		$user = JFactory::getUser();
		$extId = $user->id;
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		if (isset($extId) && !empty($extId) && $commerce == 'virtuemart') {
			$query->select(array('order_number', 'order_total'));
			$query->from('#__virtuemart_orders');
			$query->where('virtuemart_user_id = '.$extId.'');
			$query->order('virtuemart_order_id DESC LIMIT 1');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$order_id = $result->order_number;
			$cartAmount = (float)$result->order_total * 1;
		}
		elseif (isset($extId) && !empty($extId) && $commerce == 'hikashop') {
			$query->select(array('order_full_price', 'order_number'));
			$query->from('#__hikashop_order');
			$query->where('1 = 1');
			$query->order('order_id DESC LIMIT 1 ');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$order_id = $result->order_number;
			$cartAmount = (float)$result->order_full_price * 1;
		}
		elseif (isset($extId) && !empty($extId) && $commerce == 'joomshopping') {
			$query->select(array('order_number', 'order_total'));
			$query->from('#__jshopping_orders');
			$query->where('user_id = '.$extId.'');
			$query->order('order_id DESC LIMIT 1 ');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$order_id = $result->order_number;
			$cartAmount = (float)$result->order_total * 1;
		}
		elseif (isset($extId) && !empty($extId) && $commerce == 'tienda') {
			$order_id = $_REQUEST['order_id'];
			$query->select(array('order_total'));
			$query->from('#__tienda_orders');
			$query->where('user_id = '.$extId.' AND order_id = '.$order_id.'');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$cartAmount = (float)$result->order_total * 1;
		}
		elseif (isset($extId) && !empty($extId) && $commerce == 'redshop' ) {
			$order_id = ($_SESSION['__default']['order_id']);
			$query->select(array('order_total'));
			$query->from('#__redshop_orders');
			$query->where('user_id = '.$extId.' AND order_id = '.$order_id.'');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$cartAmount = (float)$result->order_total * 1;
		}
		elseif (isset($extId) && !empty($extId) && $commerce == 'j2store') {
			$order_id = ($_REQUEST['order_id']);
			$query->select(array('order_total'));
			$query->from('#__j2store_orders');
			$query->where('user_id = '.$extId.' AND order_id = '.$order_id.'');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$cartAmount = (float)$result->order_total * 1;
		}
		elseif (isset($extId) && !empty($extId) && $commerce == 'mijoshop') {
			$customId = $_SESSION['customer_id'];
			$query->select(array('order_id', 'total'));
			$query->from('#__mijoshop_order');
			$query->where('customer_id = '.$customId);
			$query->order('order_id DESC LIMIT 1 ');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$result =$results[0];
			$order_id = $result->order_id;
			$cartAmount = (float)$result->total * 1;
		}
		if ($cartAmount && $order_id) {
			$script = "<!-- START IADVIZE CONVERSION TRACKING CODE -->
				<script type=\"text/javascript\">
					var idzTrans =
					{\"tID\":\"$order_id\",
					\"cartAmount\":".$cartAmount."};
				</script>
			<!-- END IADVIZE CONVERSION TRACKING CODE -->";
		}
		return $script;
	}

/**
 * add the iAdvize tag at the bottom of the page
 * @param $script : string to insert at the end of the body, contains iadvize tags
 */
	private function idzAddTag($script) {
		$buffer = JResponse::getBody();
		$buffer = preg_replace("/<\/body>/", "\n\n" . $script . "\n\n</body>", $buffer);
		JResponse::setBody($buffer);
		return true;
	}

/**
 * Verify if an ecommerce module is activate
 * @return false or the name of the active plugin
 */
	private function idzCheckCommerce() {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('name'));
		$query->from('#__extensions');
		$query->where('enabled = 1');
		$db->setQuery($query);
		$results = ($db->loadObjectList());
		foreach ($results as $result) {
			$component[] = $result->name;
		}
		if (in_array('virtuemart', $component)) {
			$commerce = 'virtuemart';
		}
		elseif (in_array('hikashop', $component)) {
			$commerce = 'hikashop';
		}
		elseif (in_array('jshopping', $component)) {
			$commerce = 'joomshopping';
		}
		elseif (in_array('tienda', $component)) {
			$commerce = 'tienda';
		}
		elseif (in_array('com_redshop', $component)) {
			$commerce = 'redshop';
		}
		elseif (in_array('com_j2store', $component)) {
			$commerce = 'j2store';
		}
		elseif (in_array('com_mijoshop', $component)) {
			$commerce = 'mijoshop';
		}
		else {
			$commerce = false;
		}
		return $commerce;
	}

/**
 * Verify if the active page is the order confirm page
 * @param $commerce : module de commerce activé
 * @return boolean
 */
	private function idzCheckPage($commerce) {
		$page = false;
		$pagecurrent = JURI::current();
		if (strstr($pagecurrent, 'cart') && strstr($pagecurrent, 'confirm')) {
			$page = true;
		}
		elseif ($commerce == 'hikashop' && isset($_REQUEST['validate']) && $_REQUEST['validate'] == "1") {
			$page = true;
		}
		elseif ($commerce == 'joomshopping' && $_REQUEST['task'] == "finish") {
			$page = true;
		}
		elseif ($commerce == 'tienda' && ($_REQUEST['task'] == "confirmPayment" || $_REQUEST['view'] == "checkout")) {
			$page = true;
		}
		elseif ($commerce == 'redshop' && $_REQUEST['layout'] == "receipt" ) {
			$page = true;
		}
		elseif ($commerce == 'j2store' && $_REQUEST['view'] == "checkout" && $_REQUEST['task'] = "confirmPayment") {
			$page = true;
		}
		elseif ($commerce == 'mijoshop' && $_REQUEST['route'] == "checkout/success") {
			$page = true;
		}
		return $page;
	}

}
