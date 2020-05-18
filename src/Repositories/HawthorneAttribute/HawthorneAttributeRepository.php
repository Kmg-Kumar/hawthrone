<?php


namespace flexiPIM\Hawthorne\Repositories\HawthorneAttribute;

use DB;

class HawthorneAttributeRepository
{
    /**
     * Function To Insert the Hawthorne Attributes.
     *
     * @param array $insertData
     * @return mixed
     * @author KMG
     */
    public function save($insertData = array())
    {
        return DB::table('hawthorne_attribute')->insert($insertData);
    }

    /**
     * Function To Select the Hawthorne Attribute
     * @param $condition
     * @return mixed
     * @author KMG
     */
    public function select($condition = array())
    {
        return DB::table('hawthorne_attribute')->where($condition);
    }

    /**
     * Function To Insert the Attribute Mapping Fields
     *
     * @param $insertData
     * @return mixed
     * @author KMG
     */
    public function insertAttributeMapping($insertData)
    {
        return DB::table('hawthorne_attribute_mapping')->insert($insertData);
    }

    /**
     * Function To Empty the Mapped Attribute Table
     * @return mixed
     * @author KMG
     */
    public function destroyAttributeMapping()
    {
        return DB::table('hawthorne_attribute_mapping')->delete();
    }

    /**
     * Function To get the Mapped Attribute Table From the Hawthorne Attribute Mapping Table
     * @return mixed
     * @author KMG
     */
    public function selectMappedAttribute()
    {
        return DB::table('hawthorne_attribute_mapping');
    }

    /**
     * Function To Get the Mapped Attribute Code
     *
     * @return mixed
     * @param array
     * @author KMG
     */
    public function selectMappedAttributeWithCode($condition = array())
    {
        return DB::table('hawthorne_attribute_mapping')->select('hawthorne_attribute.attribute_code','pim_attribute_fields.attribute_code as pim_attribute')
            ->leftJoin('pim_attribute_fields','hawthorne_attribute_mapping.pim_attribute','=','pim_attribute_fields.id')
            ->leftJoin('hawthorne_attribute','hawthorne_attribute_mapping.hawthorne_attribute','=','hawthorne_attribute.id')->where('pim_attribute_fields.status','!=',config('constants.status.delete'))->where($condition);
    }
}
