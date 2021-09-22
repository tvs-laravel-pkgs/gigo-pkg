<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableJobOrderInvRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_order_e_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('job_order_id');
            $table->unsignedTinyInteger('e_invoice_registration')->default(0)->comment('1 -> B2B , 0 -> B2C');
            $table->string('qr_image', 191)->nullable();
            $table->string('irn_number', 191)->nullable();
            $table->string('ack_no', 191)->nullable();
            $table->dateTime('ack_date')->nullable();
            $table->string('version', 191)->nullable();
            $table->text('irn_request')->nullable();
            $table->text('irn_response')->nullable();
            $table->text('errors')->nullable();
            $table->unsignedInteger('created_by_id');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_order_e_invoices');
    }
}
