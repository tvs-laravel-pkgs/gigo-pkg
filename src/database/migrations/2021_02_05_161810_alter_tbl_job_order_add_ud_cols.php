<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTblJobOrderAddUdCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->unsignedInteger('billing_type_id')->nullable()->after('status_id');
            $table->text('warranty_reason')->nullable()->after('billing_type_id');
            $table->unsignedInteger('inward_cancel_reason_id')->nullable()->after('warranty_reason');
            $table->foreign('billing_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('inward_cancel_reason_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign('job_orders_inward_cancel_reason_id_foreign');
            $table->dropColumn('warranty_reason');
            $table->dropColumn('inward_cancel_reason_id');
            $table->dropForeign('job_orders_billing_type_id_foreign');
            $table->dropColumn('billing_type_id');
        });
    }
}
