<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableGateApssAddOutletCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->unsignedInteger('outlet_id')->nullable()->after('company_id');

			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('SET NULL')->onUpdate('cascade');
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
            $table->dropForeign('gate_passes_outlet_id_foreign');

			$table->dropColumn('outlet_id');
        });
    }
}
