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
class Manugg_LimitPerUser_Model_Enabler
{
  /**
   * Provide available options as a value/label array
   *
   * @return array
   */
  public function toOptionArray()
  {
    return array(
      array('value'=>1, 'label'=>'No'),
      array('value'=>2, 'label'=>'Yes')                    
    );
  }
}