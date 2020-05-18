<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGatePassesU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->string('gate_in_remarks',191)->nullable()->after('gate_out_date');
            $table->string('gate_out_remarks',191)->nullable()->after('gate_in_remarks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gate_passes', function (Blueprint $table) {
           $table->dropColumn('gate_in_remarks');
           $table->dropColumn('gate_out_remarks');
        });
    }
}
