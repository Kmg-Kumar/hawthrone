<?php


namespace flexiPIM\Hawthorne\Repositories\Configuration;

use DB;

class HawthorneConfigurationRepository
{
    /**
     * Function To Insert the Hawthorne Configuration
     * @param array $insertData
     * @return mixed
     * @author KMG
     */
    public function save($insertData = array())
    {
        return DB::table('hawthorne_configuration')->insert($insertData);
    }

    /**
     * Function To Select the Hawthorne Configuration
     *
     * @return mixed
     * @author KMG
     */
    public function select()
    {
        return DB::table('hawthorne_configuration')->select();
    }

    /**
     * Function To Update the Hawthorne Configuration
     *
     * @param array $data
     * @param array $condition
     * @return mixed
     */
    public function update($data = array (), $condition = array())
    {
        return DB::table('hawthorne_configuration')->where($condition)->update($data);
    }
}
