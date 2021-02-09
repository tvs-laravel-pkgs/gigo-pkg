<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobOrderAddPaymentModeCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->unsignedInteger('pending_reason_id')->nullable()->after('vehicle_payment_status');
            $table->text('pending_remarks')->nullable()->after('pending_reason_id');
            $table->foreign('pending_reason_id')->references('id')->on('pending_reasons')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign('job_orders_pending_reason_id_foreign');
            $table->dropColumn('pending_reason_id');
            $table->dropColumn('pending_remarks');
        });
    }
}
