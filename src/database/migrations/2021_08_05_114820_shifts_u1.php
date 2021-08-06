<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ShiftsU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->time('weekdays_shift_start_time')->nullable()->after('name');
            $table->time('weekdays_shift_end_time')->nullable()->after('weekdays_shift_start_time');
            $table->time('saturday_shift_start_time')->nullable()->after('weekdays_shift_end_time');
            $table->time('saturday_shift_end_time')->nullable()->after('saturday_shift_start_time');
            $table->time('sunday_shift_start_time')->nullable()->after('saturday_shift_end_time');
            $table->time('sunday_shift_end_time')->nullable()->after('sunday_shift_start_time');
            $table->time('break_start_time')->nullable()->after('sunday_shift_end_time');
            $table->time('break_end_time')->nullable()->after('break_start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('weekdays_shift_start_time');
            $table->dropColumn('weekdays_shift_end_time');
            $table->dropColumn('saturday_shift_start_time');
            $table->dropColumn('saturday_shift_end_time');
            $table->dropColumn('sunday_shift_start_time');
            $table->dropColumn('sunday_shift_end_time');
            $table->dropColumn('break_start_time');
            $table->dropColumn('break_end_time');
        });
    }
}
