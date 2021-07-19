<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableBatteryLoadResultAddSerial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('battery_load_test_results', function (Blueprint $table) {
            $table->string('number', 20)->nullable()->after('company_id');
            $table->dropForeign('battery_load_test_results_company_id_foreign');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->unique(['company_id', 'number']);
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
            $table->dropForeign('battery_load_test_results_company_id_foreign');
            $table->dropUnique('battery_load_test_results_company_id_number_unique');
            $table->dropColumn('number');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
        });
    }
}
