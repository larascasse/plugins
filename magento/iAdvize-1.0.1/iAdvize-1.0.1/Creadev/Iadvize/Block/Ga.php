<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Creadev
 * @package    Creadev_iAdvize
 * @copyright  Copyright (c) 2010 Creadev (http://www.creadev.info)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * iAdvize Page Block
 *
 * @category   Creadev
 * @package    Creadev_iAdvize
 * @author     Remi Choque <remi@creadev.info>
 */
class Creadev_Iadvize_Block_Ga extends Mage_Core_Block_Text {

	/**
	 * Prepare and return block's html output
	 *
	 * @return string
	 */
	protected function _toHtml() {

	$url = "http://www.iadvize.com/api/getcode.php?&out=wp&url=";
		/*
			Prendre en compte le https://
		*/
		$baseurl = str_replace("http://", "", Mage::getBaseUrl());
		$lastIndexOf = strrpos($baseurl, 'index');
		$length = strlen($baseurl);
		$baseurl = substr($baseurl, 0, $lastIndexOf-1);

		$url = $url.$baseurl;
		$result = file_get_contents($url);
		$this->addText("
		<!-- START IADVIZE LIVECHAT -->
			<script type='text/javascript'>
			(function() {
			var idz = document.createElement('script'); idz.type = 'text/javascript'; idz.async = true;
			idz.src = document.location.protocol + \"//lc.iadvize.com/iadvize.js?sid=11835\";
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(idz, s);
			})();
			</script>
		<!-- END IADVIZE LIVECHAT -->
        ");
		echo('id = '.$result);


		$this->addText($result);


		return parent::_toHtml();
	}
}
