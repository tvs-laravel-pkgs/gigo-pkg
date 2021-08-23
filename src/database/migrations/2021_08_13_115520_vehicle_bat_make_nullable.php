<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VehicleBatMakeNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicle_batteries', function (Blueprint $table) {
            $table->unsignedInteger('battery_make_id')->nullable()->change();
            $table->date('manufactured_date')->nullable()->change();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('battery_load_test_results', function (Blueprint $table) {
            $table->date('manufactured_date')->nullable(false)->change();
            $table->unsignedInteger('battery_make_id')->nullable(false)->change();
        });
    }
}
