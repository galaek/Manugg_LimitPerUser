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
$installer = $this;
$installer->startSetup();

$attribute  = array(
    'group'                     => 'Limit Qty Per User',
        'input'                     => 'text',
        'type'                      => 'int',
        'label'                     => 'Maximum Quantity allowed per user',
        'source'                    => 'eav/entity_attribute_source_int',
        'global'                    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'                   => 1,
        'required'                  => 0,
        'visible_on_front'          => 0,
        'is_html_allowed_on_front'  => 0,
        'is_configurable'           => 0,
        'searchable'                => 0,
        'filterable'                => 0,
        'comparable'                => 0,
        'unique'                    => false,
        'user_defined'              => false,
        'default'           => '0',
        'is_user_defined'           => false,
        'used_in_product_listing'   => true
);
$installer->addAttribute('catalog_product', 'limit_per_account', $attribute);
$installer->endSetup();

