<?php

/**
 * Hellmann
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 2.0):
 * http://www.gnu.de/documents/gpl-2.0.de.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   OrganicInternet
 * @package    OrganicInternet_SimpleConfigurableProducts
 * @license    http://www.gnu.de/documents/gpl-2.0.de.html GNU General Public License (GPL 2.0)
 */
class OrganicInternet_SimpleConfigurableProducts_Model_Observer {

    /**
     * Alter product data before indexing 
     * @param type $observer
     * @return boolean
     */
    public function updateSimpleProducts($observer) {
        $storeid = $observer->getEvent()->getStore()->getId();
        $type = $observer->getEvent()->getType();

        if ($type != 'product') {
            return true;
        }

        $products = $observer->getEvent()->getData('data');

        $currentStoreId = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore($storeid);

        $parentProducts = array();

        foreach ($products[$storeid] as $k => $product) {

            if ($product['type_id'] != 'simple') {
                continue;
            }

            // Get corresponding configurable id
            if (!$product['_parent_ids'] || !$parentId = (string) reset($product['_parent_ids'])) {
                //Mage::Log('SCP reindex: No parent_ids for '.$product['sku'].'@'.$storeid.' ('.$product['id'].')');
                continue;
            }

            // Rewrite visibility, image and url
            if (!in_array($parentId, array_keys($parentProducts))) {
                $parentProductModel = Mage::getModel('catalog/product')->load($parentId);

                try {
                    $image = (string) Mage::helper('catalog/image')->init($parentProductModel, 'thumbnail')->resize(2 * Mage::getStoreConfig('elasticsearch/product/image_size', $storeid));
                } catch (Exception $e) {
                    Mage::log("Exception in updateSimpleProducts parentId " . $parentId . " (storeid: ".$storeid."): Mage_Catalog_Helper_Image->init: [" . $e->getCode() . "]:" . $e->getMessage() . " = line " . __LINE__ . " in " . __FILE__, Zend_Log::INFO, 'exception.log');
                    $image = "";
                }
                $parentProduct = array(
                    '_url' => $parentProductModel->getProductUrl(),
                    'visibility' => $parentProductModel->getVisibility(),
                    //'image' => (string) Mage::helper('catalog/image')->init($parentProductModel, 'thumbnail')->resize(50)
                    'image' => $image
                );

                $parentProducts[$parentId] = $parentProduct;
            } else {
                $parentProduct = $parentProducts[$parentId];
            }

            $products[$storeid][$k]['_url'] = $parentProduct['_url'];
            $products[$storeid][$k]['visibility'] = $parentProduct['visibility'];
            $products[$storeid][$k]['image'] = $parentProduct['image'];
        }
        Mage::app()->setCurrentStore($currentStoreId);

        $observer->getEvent()->setData('data', $products);
    }

}
