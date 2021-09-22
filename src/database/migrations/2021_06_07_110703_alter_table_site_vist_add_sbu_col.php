<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableSiteVistAddSbuCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('on_site_orders', function (Blueprint $table) {
            $table->integer('sbu_id')->nullable()->after('outlet_id');
            $table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign('on_site_orders_sbu_id_foreign');
            $table->dropColumn('sbu_id');
        });
    }
}
