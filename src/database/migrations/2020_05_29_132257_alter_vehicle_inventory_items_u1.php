<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterVehicleInventoryItemsU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('vehicle_inventory_items', function (Blueprint $table) {
           $table->unsignedMediumInteger('display_order')->default(999)
           ->after('field_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicle_inventory_items', function (Blueprint $table) {
            $table->dropColumn("display_order");
        });
    }
}
