<?php

require_once 'Mage/Checkout/controllers/CartController.php';

class OrganicInternet_SimpleConfigurableProducts_Checkout_CartController extends Mage_Checkout_CartController {

    public function configureAction() {
        $id = (int) $this->getRequest()->getParam('id');
        $quoteItem = null;
        $cart = $this->_getCart();

        if ($id) {
            $quoteItem = $cart->getQuote()->getItemById($id);
        }

        if (!$quoteItem) {
            $this->_getSession()->addError($this->__('Quote item is not found.'));
            $this->_redirect('checkout/cart');

            return;
        }

        try {
            $params = new Varien_Object();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $params->setBuyRequest($quoteItem->getBuyRequest());

            $id = $quoteItem->getProduct()->getId();

            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($id);

            if (is_array($parentIds) && count($parentIds)) {
                $id = current($parentIds);
            }

            Mage::helper('catalog/product_view')->prepareAndRender($id, $this, $params);
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Cannot configure product.'));

            Mage::logException($e);

            $this->_goBack();

            return;
        }
    }

    /*
     * Fix configure item in cart bug
     * https://github.com/obigroup/magento-configurable-simple/issues/1
     * 
     */
    public function updateItemOptionsAction() {
        $cart = $this->_getCart();
        $params = $this->getRequest()->getParams();
        $product = $this->_initProduct();

        //$id = (int)$this->getRequest()->getParam('id');
        $id = $product->getId();
        $quoteItem = $cart->getQuote()->getItemById($id);

        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($id);
        if (is_array($parentIds) && count($parentIds)) {
            $id = current($parentIds);
        }
        $_product = Mage::getModel('catalog/product')->load($id);

        if ($_product->getTypeId() == 'configurable') {
            $old_product_item_id = (int) $params['id'];
            unset($params['id']);
            $new_product = $this->_initProduct();
            try {

                $cart->removeItem($old_product_item_id);
                $cart->addProduct($new_product, $params);
                $cart->save();
                $this->_getSession()->setCartWasUpdated(true);
                $message = $this->__('%s was updated in your shopping cart.', Mage::helper('core')->escapeHtml($new_product->getName()));
                $this->_getSession()->addSuccess($message);
            } catch (Exception $e) {
                if ($this->_getSession()->getUseNotice(true)) {
                    $this->_getSession()->addNotice($e->getMessage());
                } else {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message) {
                        $this->_getSession()->addError($message);
                    }
                }                
            }
        } else {

            $cart = $this->_getCart();
            $id = (int) $this->getRequest()->getParam('id');
            $params = $this->getRequest()->getParams();

            if (!isset($params['options'])) {
                $params['options'] = array();
            }
            try {
                if (isset($params['qty'])) {
                    $filter = new Zend_Filter_LocalizedToNormalized(
                            array('locale' => Mage::app()->getLocale()->getLocaleCode())
                    );
                    $params['qty'] = $filter->filter($params['qty']);
                }

                $quoteItem = $cart->getQuote()->getItemById($id);
                if (!$quoteItem) {
                    Mage::throwException($this->__('Quote item is not found.'));
                }

                $item = $cart->updateItem($id, new Varien_Object($params));
                if (is_string($item)) {
                    Mage::throwException($item);
                }
                if ($item->getHasError()) {
                    Mage::throwException($item->getMessage());
                }

                $related = $this->getRequest()->getParam('related_product');
                if (!empty($related)) {
                    $cart->addProductsByIds(explode(',', $related));
                }

                $cart->save();

                $this->_getSession()->setCartWasUpdated(true);

                Mage::dispatchEvent('checkout_cart_update_item_complete', array('item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                );
                if (!$this->_getSession()->getNoCartRedirect(true)) {
                    if (!$cart->getQuote()->getHasError()) {
                        $message = $this->__('%s was updated in your shopping cart.', Mage::helper('core')->escapeHtml($item->getProduct()->getName()));
                        $this->_getSession()->addSuccess($message);
                    }
                    $this->_goBack();
                }
            } catch (Mage_Core_Exception $e) {
                if ($this->_getSession()->getUseNotice(true)) {
                    $this->_getSession()->addNotice($e->getMessage());
                } else {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message) {
                        $this->_getSession()->addError($message);
                    }
                }

                $url = $this->_getSession()->getRedirectUrl(true);
                if ($url) {
                    $this->getResponse()->setRedirect($url);
                } else {
                    $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
                }
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Cannot update the item.'));
                Mage::logException($e);
                $this->_goBack();
            }
        }

        $this->_redirect('checkout/cart');
    }
    
}
