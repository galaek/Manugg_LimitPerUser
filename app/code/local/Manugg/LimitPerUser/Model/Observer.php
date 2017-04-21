<?php 
/**
 * Manugg_LimitPerUser extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Open Software License (OSL)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * 
 * @category       Manugg
 * @package        Manugg_LimitPerUser
 * @copyright      Copyright (c) 2017
 * @license        https://opensource.org/licenses/osl-3.0.php Open Software License (OSL)
 */
class Manugg_LimitPerUser_Model_Observer
{

    /**
     * No account can order above the defined limit for each product
     */
    public function enforceLimitPerUser($observer)
    {
		$moduleEnabled = Mage::getStoreConfig('limitperuser_options/section_enabler/field_enabled');
		$defaultMessage = Mage::getStoreConfig('limitperuser_options/section_message/show_default_message');
		Mage::log('Modulo habilitado?: ' . $moduleEnabled , null, 'testForm.log');
		Mage::log('Mostrar Default Message?: ' . $defaultMessage , null, 'testForm.log');
		Mage::log('Evento checkout_cart_update_items_after capturado ' , null, 'testUpdateCart.log');
		if ($moduleEnabled == 2) { // Module enabled
			if ($observer->getInfo() == null) {
				//Mage::log('Info viene vacio', null, 'testUpdateCart.log');
			} else {
				$data = $observer->getInfo();
				$i = 0; $j = 0;
				foreach ($data as $item) {
					$j = 0 ;
					foreach ($item as $itemInner) {
						if ($j == 0) {
							//Mage::log('(' . $i . ','. $j . ')' . gettype($itemInner) . ' ' . $itemInner, null, 'testUpdateCart.log');
							$qtys[$i] =  $itemInner;
						}
						$j = $j + 1;
					}
					$i = $i + 1;
				}
				
			}
			if ($observer->getCart() == null) {
				//Mage::log('Cart viene vacio', null, 'testUpdateCart.log');
			} else {
				$data = $observer->getCart();
				$quote = $data->getQuote();
				
				$collection = $data->getItems();
				$i = 0;
				foreach ($collection as $item) {
					$product = $item->getProduct();
					$productId = $product->getIdBySku($product->getSku());
					$ids[$i] = $productId;
					$productQtys[$productId] = $qtys[$i];
					$i = $i + 1;
				}
				
			
				$orders= Mage::getModel('sales/order')->getCollection()->addAttributeToFilter('customer_id',Mage::getSingleton('customer/session')->getCustomer()->getId());
				foreach($orders as $eachOrder){
					$order = Mage::getModel("sales/order")->load($eachOrder->getId()); 

					$items = $order->getAllVisibleItems();
					foreach($items as $item) {
					  $productsList[$item->getProductId()] = $productsList[$item->getProductId()] + 1;
					}
				}
				$printMessage = false;
				for ($x = 0; $x < sizeof($ids); $x++) {
					$totalQty = $productQtys[$ids[$x]];
					$productId = $ids[$x];
					$product = Mage::getModel('catalog/product')->load($productId); 
					$limitPerUser = $product->getData('limit_per_account'); if ($limitPerUser == null) {$limitPerUser=0;}
					$comprados = $productsList[$ids[$x]];
					if ($comprados == null) $comprados = 0;
					Mage::log('Id: ' . $productId . ' Qty carrito: ' . $totalQty . ' comprados: '. $comprados . ' limite: ' . $limitPerUser, null, 'testUpdateCart.log');
				
					if ($limitPerUser == 0) {
						// There is no limit for this product
					} else if (($totalQty+$comprados) <= $limitPerUser) {
						// QTY is OK
					} else {
						// QTY exceeds the limit
						$available = $limitPerUser - $comprados; // We could also check stock availability...
						if ($defaultMessage == 2) {
							$errorMsg = Mage::helper('limitperuser')->__('For product &quot;%s&quot; only %s unit(s) can be bought per account. You already bought %s and you can still buy %s more.',
											$product->getData('name'), $limitPerUser, $comprados, $available);
							Mage::getSingleton('checkout/session')->addError($errorMsg);
						}
						
						$printMessage = true;

						$quote = $observer->getCart()->getQuote();
						$quoteItem = $quote->getItemByProduct($product);
						$quoteItem->setQty($available);
					}
					
				}
				$backendMessage = Mage::getStoreConfig('limitperuser_options/section_message/custom_message');
				if (($backendMessage != null) && ($printMessage == true)) {
					Mage::getSingleton('checkout/session')->addError($backendMessage);
				}
			}
		}
    }
	public function enforceLimitAddProduct(Varien_Event_Observer $observer)
	{
		$cartEnabledGuests = Mage::getStoreConfig('limitperuser_options/section_logged_cart/disable_cart');
		$datos = $observer->getEvent()->getProduct();
		Mage::log($datos->getProductUrl(), null, 'testLogged.log');
		Mage::log($datos->getUrlPath(), null, 'testLogged.log');
		Mage::log(get_class($datos), null, 'testLogged.log');
		if ((!Mage::getSingleton('customer/session')->isLoggedIn()) && ($cartEnabledGuests == 2)) {
			Mage::log('Not Logged in', null, 'testLogged.log');
			Mage::getSingleton('customer/session')->setBeforeAuthUrl($datos = $observer->getEvent()->getProduct()->getProductUrl());
			$response = Mage::app()->getFrontController()->getResponse();
			$response->setRedirect(Mage::getUrl("customer/account/login"));
			$response->sendResponse();
			$controller = $observer->getData('controller_action');
			$controller->setFlag(
				 $controller->getRequest()->getActionName(),
				 Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH,
				 true
			 );
		}
		else {	
			$moduleEnabled = Mage::getStoreConfig('limitperuser_options/section_enabler/field_enabled');
			$defaultMessage = Mage::getStoreConfig('limitperuser_options/section_message_add/show_default_message_add');
			$backendMessage = Mage::getStoreConfig('limitperuser_options/section_message_add/custom_message_add');
			if ($moduleEnabled == 2) {
				Mage::log('Evento de añadir producto capturado.' . $moduleEnabled , null, 'testAddP.log');
				$limitPerUser = $observer->getEvent()->getProduct()->getData('limit_per_account');
				$prodName = $observer->getEvent()->getProduct()->getData('name');
				if (($limitPerUser != null) && ($limitPerUser != 0)) {
					if(!Mage::getSingleton('customer/session')->isLoggedIn()) {
						Mage::log('Añadiendo producto no logeado', null, 'testAddP.log');
						$quoteItem = $observer->getQuoteItem();
						Mage::log(get_class($quoteItem), null, 'testAddP.log');
						$quoteItem->getQuote()->removeItem($quoteItem->getId());
						$errorMsg = Mage::helper('limitperuser')->__('The product &quot;%s&quot; has a quantity limit per user. You must be logged in to add it to the cart.',
												$prodName);
						if ($defaultMessage == 2) {
							Mage::getSingleton('checkout/session')->addError($errorMsg);
						}
 						$response = Mage::app()->getFrontController()->getResponse();
						$response->setRedirect(Mage::getUrl("checkout/cart/"));
						$response->sendResponse();
						$controller = $observer->getData('controller_action');
						$controller->setFlag(
							 $controller->getRequest()->getActionName(),
							 Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH,
							 true
						 );
					} else {
						$orders= Mage::getModel('sales/order')->getCollection()->addAttributeToFilter('customer_id',Mage::getSingleton('customer/session')->getCustomer()->getId());
						foreach($orders as $eachOrder){
							$order = Mage::getModel("sales/order")->load($eachOrder->getId()); 

							$items = $order->getAllVisibleItems();
							foreach($items as $item) {
							  $productsList[$item->getProductId()] = $productsList[$item->getProductId()] + 1;
							}
						}
						Mage::log(get_class($observer->getQuoteItem()), null, 'testNumProducts.log');
						$quoteItem = $observer->getQuoteItem();
						$totalQty = $quoteItem->getQty();
						$addedQty = $observer->getEvent()->getProduct()->getData('qty');
						$limitPerUser = $observer->getEvent()->getProduct()->getData('limit_per_account');
						$product = $observer->getEvent()->getProduct();
						$productId = $product->getIdBySku($product->getSku());
						$comprados = $productsList[$productId]; if ($comprados==null) $comprados=0;
						Mage::log('Producto añadido id: ' . $productId . ' Cantidad total al carrito: ' . $totalQty . ' limite: ' . $limitPerUser . ' comprados: ' . $comprados, null, 'testNumProducts.log');
						
						if (($limitPerUser == null) || ($limitPerUser==0)) {
							// There is no limit for this product
						} else if (($totalQty+$comprados) <= $limitPerUser) {
							// QTY is OK
						} else {
							// QTY exceeds the limit
							$available = $limitPerUser - $comprados;
							$errorMsg = Mage::helper('limitperuser')->__('For product &quot;%s&quot; only %s unit(s) can be bought per account. You already bought %s and you can still buy %s more.',
												$observer->getEvent()->getProduct()->getData('name'), $limitPerUser, $comprados, $available);
							Mage::getSingleton('checkout/session')->addError($errorMsg);
							$quoteItem->setQty($available);
							if ($backendMessage != null) {
								Mage::getSingleton('checkout/session')->addError($backendMessage);
							}
							/* $response = Mage::app()->getFrontController()->getResponse();
							$response->setRedirect(Mage::getUrl("checkout/cart/"));
							$response->sendResponse();
							die; */
						}
					}
				}
			}
		}
	}
}
