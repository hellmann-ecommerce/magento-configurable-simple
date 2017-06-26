<?php

/**
 * Nexcess.net Turpentine Extension for Magento
 * Copyright (C) 2012  Nexcess.net L.L.C.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class OrganicInternet_SimpleConfigurableProducts_Helper_Turpentine_Data extends Nexcessnet_Turpentine_Helper_Data {

    /**
     * Get the getModel formatted name of a model classname or object
     *
     * @param  string|object $model
     * @return string
     */
    public function getModelName($model) {

        if (is_object($model)) {
            $model = get_class($model);
        }

        // mplutka@hellmann.com: Check if class could really be loaded
        // organicinternet_simpleconfigurableproducts_catalog_model_product can't be resolved to a real model's file name for include
        // https://github.com/nexcess/magento-turpentine/issues/974
        if('organicinternet_simpleconfigurableproducts' == substr($model,0,strlen('organicinternet_simpleconfigurableproducts'))) {
            return $model;
        }

        return parent::getModelName($model);
    }
}
