<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CustomerVoicesAddRepairOrderId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('customer_voices', function (Blueprint $table) {
                $table->unsignedInteger('repair_order_id')->nullable()->after('name');
                $table->foreign('repair_order_id')->references('id')->on('repair_orders')->onDelete('SET NULL')->onUpdate('cascade');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
            Schema::table('customer_voices', function (Blueprint $table) {
                $table->dropForeign('customer_voices_repair_order_id_foreign');
                $table->dropColumn('repair_order_id');
            });
    }
}
