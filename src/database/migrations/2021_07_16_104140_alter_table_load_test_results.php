<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLoadTestResults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('battery_load_test_results', function (Blueprint $table) {
            $table->string('job_card_number')->nullable()->after('replaced_battery_serial_number');
            $table->date('job_card_date')->nullable()->after('job_card_number');

            $table->string('invoice_number')->nullable()->after('job_card_date');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->unsignedDecimal('invoice_amount',10,2)->nullable()->after('invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('battery_load_test_results', function (Blueprint $table) {
            $table->dropColumn('job_card_number');
            $table->dropColumn('job_card_date');
            $table->dropColumn('invoice_number');
            $table->dropColumn('invoice_date');
            $table->dropColumn('invoice_amount');
        });
    }
}
