<?php


namespace flexiPIM\Hawthorne\Repositories\LogRepository;

use DB;

class LogRepository
{
    /**
     * Function To List the Log List
     *
     * @return mixed
     * @author KMG
     */
    public function selectLog()
    {
        return DB::table('hawthorne_log')->select('hawthorne_log.*',
            'users.username')
            ->leftJoin('users','hawthorne_log.created_by','=','users.id')
            ->orderBy('id','desc')->take(10)->get();
    }

    /**
     * Function to get the Active Log List
     *
     * @return mixed
     * @author KMG
     */
    public function activeLog()
    {
        return DB::table('hawthorne_log')->where('status','processing');
    }
}
