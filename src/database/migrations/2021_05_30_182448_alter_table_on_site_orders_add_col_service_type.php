<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOnSiteOrdersAddColServiceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('on_site_orders', function (Blueprint $table) {
            $table->unsignedInteger('amc_customer_id')->nullable()->after('customer_id');
            $table->foreign('amc_customer_id')->references('id')->on('amc_customers')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('on_site_orders', function (Blueprint $table) {
            $table->dropForeign('on_site_orders_amc_customer_id_foreign');
            $table->dropColumn('amc_customer_id');
        });
    }
}
