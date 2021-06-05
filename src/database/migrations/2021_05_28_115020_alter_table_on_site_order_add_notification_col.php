<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOnSiteOrderAddNotificationCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('on_site_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('notification_sent_status')->default(1)->after('customer_id')->comment('1 -> Yes 0 -> No');
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
            $table->dropColumn('notification_sent_status');
        });
    }
}
