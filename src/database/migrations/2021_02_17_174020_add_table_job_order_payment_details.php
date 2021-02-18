<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableJobOrderPaymentDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('job_order_payment_details')) {
            Schema::create('job_order_payment_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('job_order_id');
                $table->unsignedInteger('payment_mode_id')->nullable();
                $table->string('transaction_number');
                $table->date('transaction_date')->nullable();
                $table->unsignedDecimal('amount',16,2);

                $table->unique(["job_order_id", "transaction_number"]);

                $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('payment_mode_id')->references('id')->on('payment_modes')->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_order_payment_details');
    }
}
