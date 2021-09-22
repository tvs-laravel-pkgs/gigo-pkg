<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BatteryLoadTestResultsTableClone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE battery_load_test_results_backup1 LIKE battery_load_test_results; ');
        DB::statement('INSERT battery_load_test_results_backup1 SELECT * FROM battery_load_test_results');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('battery_load_test_results_backup1');
    }
}
