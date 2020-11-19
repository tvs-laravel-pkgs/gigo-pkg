<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobOrderRepairOrderAddFaultComplaintCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_order_repair_orders', function (Blueprint $table) {
            $table->unsignedInteger('complaint_id')->nullable()->after('removal_reason');
            $table->unsignedInteger('fault_id')->nullable()->after('complaint_id');

            $table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('fault_id')->references('id')->on('faults')->onDelete('SET NULL')->onUpdate('cascade');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_order_repair_orders', function (Blueprint $table) {
            $table->dropForeign('job_order_repair_orders_complaint_id_foreign');
            $table->dropForeign('job_order_repair_orders_fault_id_foreign');

            $table->dropColumn('complaint_id');
            $table->dropColumn('fault_id');
		});
    }
}
