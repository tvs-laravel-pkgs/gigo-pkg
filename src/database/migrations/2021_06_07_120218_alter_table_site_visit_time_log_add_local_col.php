<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableSiteVisitTimeLogAddLocalCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('on_site_order_time_logs', function (Blueprint $table) {
            $table->string('start_latitude', 40)->nullable()->after('start_date_time');
            $table->string('start_longitude', 40)->nullable()->after('start_latitude');

            $table->string('end_latitude', 40)->nullable()->after('end_date_time');
            $table->string('end_longitude', 40)->nullable()->after('end_latitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('on_site_order_time_logs', function (Blueprint $table) {
            $table->dropColumn('start_latitude');
            $table->dropColumn('start_longitude');
            $table->dropColumn('end_latitude');
            $table->dropColumn('end_longitude');
        });
    }
}
