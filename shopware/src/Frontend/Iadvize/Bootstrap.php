<?php
/**
 * Shopware iAdvize plugin 1.0
 * Copyright © 2013 iAdvize
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "iAdvize" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage iAdvize
 * @copyright  Copyright (c) 2012, iAdvize (http://www.iadvize.com)
 * @version    $Id$
 * @author     Martin Supiot
 * @author     $Author$
 */

/**
 * Shopware iAdvize Plugin
 */
class Shopware_Plugins_Frontend_Iadvize_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	/**
	 * Plugin temp vars
	 * Used to store vars between hooks (event listeners)
	 *
	 * @var array
	 */
	protected $vars = array();

	/**
	 * Define this plugin capabilities
	 *
	 * @return array This plugin capabilities
	 */
	public function getCapabilities() {
		return array(
			'install' => true,
			'update' => true,
			'enable' => true
		);
	}

	/**
	 * Get this plugin name
	 *
	 * @return string This plugin name
	 */
	public function getLabel() {
		return 'iAdvize';
	}

	/**
	 * Get this plugin version number
	 *
	 * @return string This plugin version number
	 */
	public function getVersion() {
		return '1.0.0';
	}

	/**
	 * Return iAdvize plugin informations
	 *
	 * @return array iAdvize plugin informations
	 */
	public function getInfo() {
		return array(
			'version' => $this->getVersion(),
			'label' => $this->getLabel(),
			'supplier' => 'iAdvize',
			'description' => 'Chat plugin by iAdvize',
			'author' => 'iAdvize',
			'copyright' => 'Copyright © 2013, iAdvize',
			'support' => 'support@iadvize.com',
			'link' => 'http://www.iadvize.com'
		);
	}

	/**
	 * Install this plugin
	 *
	 * @return boolean true because install is ever ok
	 */
	public function install() {

		$id = $this->getIadvizeId();
		$this->setIadvizeConfig($id);
		$this->registerEvents();
		return true;
	}

	/**
	 * Define iAdvize id in shopware config
	 *
	 * @param integer $id Th eiAdvize client identifier
	 */
	public function setIadvizeConfig($id) {
		$form = $this->Form();
		$parent = $this->Forms()->findOneBy(array('name' => 'Interface'));
		$form->setParent($parent);
		$form->setElement('text', 'tracking_code', array(
			'label' => 'iAdvize identifier',
			'value' => $id,
			'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
		));
	}

	/**
	 * Uninstal and free cache
	 *
	 * @return true if uninstall and empty cache successfull, false otherwise
	 */
	public function uninstall() {

		return array('success' => true, 'invalidateCache' => array('frontend'));
	}

	/*
	 * Register some events.
	 *
	 * @return void
	 */
	protected function registerEvents() {
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Index',
			'onPostDispatch'
		);
		$this->subscribeEvent(
			'Shopware_Modules_Order_SaveOrder_FilterDetailsSQL',
			'shopwareModulesOrderSaveOrderFilterDetailsSQL'
		);
	}

	/**
	 * Check transaction
	 *
	 * @param  Enlight_Event_EventArgs $args Shopware args
	 * @return void
	 */
	public function shopwareModulesOrderSaveOrderFilterDetailsSQL(Enlight_Event_EventArgs $args) {

		if (isset($args->getSubject()->sAmount)) {
			$this->vars['amount'] = $args->getSubject()->sAmount;
		}
		if (isset($args->getSubject()->sOrderNumber)) {
			$this->vars['orderNumber'] = $args->getSubject()->sOrderNumber;
		}
	}

	/**
	 * Event listener method
	 *
	 * @param Enlight_Event_EventArgs Shopware args
	 * @return void
	 */
	public function onPostDispatch(Enlight_Event_EventArgs $args) {

		$controller = $args->getSubject();
		$request = $controller->Request();
		$response = $controller->Response();
		$view = $controller->View();
		$amount = null;

		// Set template
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/iadvize/index.tpl');

		if (!$request->isDispatched() || $response->isException()
			|| $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
				return;
		}

		// Check tracking code
		$config = Shopware()->Plugins()->Frontend()->Iadvize()->Config();
		if (empty($config->tracking_code)) {
			return;
		}
		else {
			$view->iAdvizeId = $config->tracking_code;
		}

		// Check transaction
		if (!is_null($this->vars['amount']) && !is_null($this->vars['orderNumber'])) {
			$view->sCartAmount = $this->vars['amount'];
			$view->sTransactionId = $this->vars['orderNumber'];
		}
	}

	/**
	 * Return the iAdvize id corresponding to the domain name defined in iAdvize backend
	 *
	 * @return integer The iAdvize customer identifier
	 */
	protected function getIadvizeId() {
		$url = 'http://www.iadvize.com/api/getcode.php?&out=wp&url='
			. str_replace("http://", "", $_SERVER['HTTP_HOST']);

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// User agent that mimics a browser
			curl_setopt($ch, CURLOPT_USERAGENT,
				'Mozilla/5.0 (Windows; U; WindowsNT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
			$result = curl_exec($ch);
			curl_close($ch);
		}
		else {
			// curl library is not compiled in so use file_get_contents.
			$result = file_get_contents($url);
		}
		return intval($result);
	}
}
