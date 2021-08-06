<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MechanicTimeLogsU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mechanic_time_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('cron_status')->default(0)->after('status_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mechanic_time_logs', function (Blueprint $table) {
            $table->dropColumn('cron_status');
        });
    }
}
