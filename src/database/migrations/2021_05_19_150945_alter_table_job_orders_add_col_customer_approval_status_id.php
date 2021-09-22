<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrdersAddColCustomerApprovalStatusId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->unsignedInteger('customer_approval_status_id')->nullable()->after('status_id');
            $table->foreign('customer_approval_status_id', 'customer_approval_status_id_foreign')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign('customer_approval_status_id_foreign');
            $table->dropColumn('customer_approval_status_id');
        });
    }
}
