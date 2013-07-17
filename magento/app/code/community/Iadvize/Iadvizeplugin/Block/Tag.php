<?php
/**
 * Get the iAdvize ID in data base
 */
	function _getIadvizeId() {
		return Mage::getStoreConfig('iadvize_iadvizeplugin/general/iadvize_id');
		}
class Iadvize_Iadvizeplugin_Block_Tag extends Mage_Core_Block_Text {
	/**
	 * Prepare and return block's html output, with tag's iAdvize live chat
	 * and adding the tracking conversion code, after cart's validation
	 * @return string
	 */

	protected function _toHtml() {
		$onepage = $this->getRequest()->getControllerName();
		$success = $this->getRequest()->getActionName();
		$checkout = $this->getRequest()->getRouteName();
		/**
		 * Verify if the page take place after validation of the cart
		 */
		if ($onepage == 'onepage' && $success == 'success' && $checkout == 'checkout') {
			$orders = Mage::getModel('sales/order')->getCollection()
			->setOrder('increment_id', 'DESC')
			->setPageSize(1)
			->setCurPage(1);
			$order = Mage::getModel('sales/order')->loadByIncrementId
			(Mage::getSingleton('checkout/session')->getLastRealOrderId());
			$ref = $orders->getFirstItem()->getIncrementId();
			$cartAmount = number_format($order->getGrandTotal(), 2);
			if (isset($cartAmount) && !empty($cartAmount)) {
				/**
				 * Add iAdvize tag for conversion tracking
				 */
					$this->addText("
				<!-- START IADVIZE CONVERSION TRACKING CODE -->
					<script type=\"text/javascript\">
						var idzTrans =
						{\"tID\":\"".$ref."\",\"cartAmount\":$cartAmount};
					</script>
				<!-- END IADVIZE CONVERSION TRACKING CODE -->
				");
			}
		}
		/**
		 * Get the iadvize ID from iAdvize Api
		 */
		$url = "http://www.iadvize.com/api/getcode.php?&out=wp&url=";
		if (!$result = _getIadvizeId() && empty($result)) {
			$baseurl = str_replace("http://", "", Mage::getBaseUrl());
			$lastIndexOf = strrpos($baseurl, 'index');
			$length = strlen($baseurl);
			$baseurl = substr($baseurl, 0, $lastIndexOf-1);
			$url = $url.$baseurl;
			$result = file_get_contents($url);
			Mage::getModel('core/config')->saveConfig('iadvize_iadvizeplugin/general/iadvize_id', $result );
		}
		else {
			$result = _getIadvizeId();
		}
		/**
		 * Add iadvize tag for LIVECHAT
		 */
		$this->addText("
		<!-- START IADVIZE LIVECHAT -->
			<script type='text/javascript'>
			(function() {
			var idz = document.createElement('script'); idz.type = 'text/javascript'; idz.async = true;
			idz.src = document.location.protocol + \"//lc.iadvize.com/iadvize.js?sid=".$result."\";
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(idz, s);
			})();
			</script>
		<!-- END IADVIZE LIVECHAT -->
		");
		return parent::_toHtml();
	}
}
