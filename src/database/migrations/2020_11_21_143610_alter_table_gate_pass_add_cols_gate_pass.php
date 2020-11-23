<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableGatePassAddColsGatePass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->unsignedInteger('purpose_id')->nullable()->after('entity_id');
            $table->string('other_remarks')->nullable()->after('purpose_id');
            $table->string('hand_over_to',40)->nullable()->after('other_remarks');
            $table->foreign('purpose_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign('gate_passes_purpose_id_foreign');

            $table->dropColumn('purpose_id');
            $table->dropColumn('other_remarks');
			$table->dropColumn('hand_over_to');
		});
    }
}
