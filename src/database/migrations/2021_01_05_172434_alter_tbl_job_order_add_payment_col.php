<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTblJobOrderAddPaymentCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->tinyInteger('vehicle_payment_status')->nullable()->after('estimation_approved_at')->comment('1 -> Yes 0 -> No');
            $table->unsignedInteger('vehicle_delivery_requester_id')->nullable()->after('vehicle_payment_status');
            $table->text('vehicle_delivery_request_remarks')->nullable()->after('vehicle_delivery_requester_id');
            $table->unsignedInteger('approver_id')->nullable()->after('vehicle_delivery_request_remarks');
            $table->text('approved_remarks')->nullable()->after('approver_id');
            $table->dateTime('approved_date_time')->nullable()->after('approved_remarks');

            $table->foreign('vehicle_delivery_requester_id','vehicle_delivery_requester_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

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
            $table->dropForeign('vehicle_delivery_requester_id');
            $table->dropForeign('job_orders_approver_id_foreign');
            $table->dropColumn('vehicle_payment_status');
            $table->dropColumn('vehicle_delivery_request_remarks');
            $table->dropColumn('approver_id');
            $table->dropColumn('approved_remarks');
            $table->dropColumn('vehicle_delivery_requester_id');
            $table->dropColumn('approved_date_time');

		});
    }
}
