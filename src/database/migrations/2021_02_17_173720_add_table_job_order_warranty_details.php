<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableJobOrderWarrantyDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('job_order_warranty_details')) {
            Schema::create('job_order_warranty_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('job_order_id');
                $table->string('number');
                $table->date('warranty_date')->nullable();
                $table->unsignedDecimal('labour_amount',16,2)->nullable();
                $table->unsignedDecimal('parts_amount',16,2)->nullable();

                $table->unique('number');
                
                $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('job_order_warranty_details');
    }
}
