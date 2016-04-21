<?php
class OrganicInternet_SimpleConfigurableProducts_Catalog_Model_Product_Type_Simple
    extends Mage_Catalog_Model_Product_Type_Simple
{
    
    #Later this should be refactored to live elsewhere probably,
    #but it's ok here for the time being
    private function getCpid()
    {
        
        $cpid = $this->getProduct()->getCustomOption('cpid');
        if ($cpid) {
            return $cpid;
        }

        $br = $this->getProduct()->getCustomOption('info_buyRequest');
        if ($br) {
            $brData = unserialize($br->getValue());
            if(!empty($brData['cpid'])) {
                return $brData['cpid'];
            }
        }

        return false;
    }

    public function prepareForCart(Varien_Object $buyRequest, $product = null)
    {
        $product = $this->getProduct($product);
        parent::prepareForCart($buyRequest, $product);
        if ($buyRequest->getcpid()) {
            $product->addCustomOption('cpid', $buyRequest->getcpid());
        }
        return array($product);
    }

    public function hasConfigurableProductParentId()
    {
        $cpid = $this->getCpid();
        //Mage::log("cpid: ". $cpid);
        return !empty($cpid);
    }

    public function getConfigurableProductParentId()
    {
        return $this->getCpid();
    }
  
    
    
    /**
     * Method taken from configurable product in order to fill attributes_info array
     * 
     * @param type $product
     * @return type
     */
    public function getOrderOptions($product = null)
    {               
        $options = parent::getOrderOptions($product);
        $options['attributes_info'] = $this->getSelectedAttributesInfo($product);
        
        $options['simple_name'] = $product->getName();
        $options['simple_sku']  = $product->getSku();

        $options['product_calculations'] = self::CALCULATE_PARENT;
        $options['shipment_type'] = self::SHIPMENT_TOGETHER;

        return $options;
    }

    /**
     * Retrieve Selected Attributes info and take into account that we don't habe a configurable product here
     * 
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */

    public function getSelectedAttributesInfo($product = null)
    {
        // Since we don't have a configurable product with the selected attributes in the basket,
        // we use the info_buyRequest object to retrieve the attributes and their values
        $attributes_info = array();
        
        if($infoBuyRequest = $product->getCustomOption('info_buyRequest')) {
            $infoBuyRequestData = unserialize($infoBuyRequest->getValue());
            $superAttributes    = $infoBuyRequestData['super_attribute'];
            if(!is_array($superAttributes)) {
                return array();
            }
            foreach($superAttributes as $attributeId => $attributeValue) {    
                $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                if(!$attribute) {
                    continue;
                }

                $label = $attribute->getStoreLabel();
               
                $value = $attribute->getSource()->getOptionText($attributeValue);           
                $attributes_info[] = array('label' => $label, 'value' => $value);
            }
        }  
                
        return $attributes_info;        
    } 
}
