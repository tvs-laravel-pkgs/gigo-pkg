<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RepairOrderServiceTypeC extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('repair_order_service_type')) {
            Schema::create('repair_order_service_type', function (Blueprint $table) {

                $table->unsignedInteger('repair_order_id');
                $table->unsignedInteger('service_type_id');

                $table->foreign("repair_order_id")->references("id")->on("repair_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
                $table->foreign("service_type_id")->references("id")->on("service_types")->onDelete("CASCADE")->onUpdate("CASCADE");

                $table->unique(['repair_order_id', 'service_type_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repair_order_service_type');
    }
}
