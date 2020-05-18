<?php


namespace flexiPIM\Hawthorne\Helpers;

use DB;

class HawthorneHelper
{
    /**
     * Function To check the SKU3 Present Or Not, If Present To Return the
     * Product Data Else Return false
     * @param string $sku3
     * @return mixed
     * @author KMG
     */
//    public function isSku3Present($sku3 = '')
//    {
//        $attributeDetails = isAttributePresent('sku_3','');
//        if(isset($attributeDetails)){
//            return DB::table($attributeDetails->entity_table_name)
//                ->leftJoin('pim_products',$attributeDetails->entity_table_name.'.product_id','=','pim_products.id')
//                ->where('attribute_id',$attributeDetails->id)
//                ->where('product_attribute_values',$sku3)
//                ->where('pim_products.status','!=',config('constants.status.delete'))
//                ->first();
//        }
//        return null;
//    }

    /**
     * Function To check the SKU2 Present Or Not, If Present To Return the
     * Product Data Else Return false
     * @param string $sku2
     * @return mixed
     * @author KMG
     */
    public function isSku2Present($sku2 = '')
    {
        $attributeDetails = isAttributePresent('sku_2','');
        if(isset($attributeDetails)){
            return DB::table($attributeDetails->entity_table_name)
                ->leftJoin('pim_products',$attributeDetails->entity_table_name.'.product_id','=','pim_products.id')
                ->where('attribute_id',$attributeDetails->id)
                ->where('product_attribute_values',$sku2)
                ->where('pim_products.status','!=',config('constants.status.delete'))
                ->first();
        }
        return null;
    }
}
