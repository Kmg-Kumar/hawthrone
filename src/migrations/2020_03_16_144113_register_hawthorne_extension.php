<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RegisterHawthorneExtension extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $insertData = [
            'extension_name' => 'Hawthorne Supplier Connector',
            'extension_logo' => 'https://cdn.flexipim.com/extension/hawthorne.jpg',
            'url_key' => 'hawthorne',
            'description' => 'Hawthorne Supplier Connector',
            'status' => 1,
            'extension_status' => 0,
            'is_global' => 0,
            'price' => 400,
            'enable_date' => Date('Y-m-d H:i:s'),
            'created_at' => Date('Y-m-d H:i:s'),
            'updated_at' => Date('Y-m-d H:i:s'),
        ];
        $extensionId = DB::table('pim_extensions')->insertGetId($insertData);

        $permissionArray = array();

        foreach(fetchUserListByRole('Super Admin')->pluck('id')->toArray() as $value){
            $permissionArray[] = array('user_id' => $value,
                'extension_id' => $extensionId);
        }
        if(Schema::hasTable('pim_extension_permissions')){
            DB::table('pim_extension_permissions')->insert($permissionArray);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
