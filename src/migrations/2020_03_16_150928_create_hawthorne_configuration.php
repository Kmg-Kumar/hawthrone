<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHawthorneConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hawthorne_configuration', function (Blueprint $table) {
            $table->increments('id');
            $table->string('access_url');
            $table->string('client_key');
            $table->string('secret_key');
            $table->string('products_access_url');
            $table->integer('channel_id');
            $table->integer('category_id');
            $table->integer('family_id');
            $table->string('cron_time');
            $table->timestamp('last_sync_date')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hawthorne_configuration');
    }
}
