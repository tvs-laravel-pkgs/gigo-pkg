<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableManualGigoPaymentStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::create('gigo_manual_invoice_payment_statuses', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name', 100);
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->unique(['name']);
			$table->foreign('created_by_id','created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id','updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id','deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	public function down() {
		Schema::dropIfExists('gigo_manual_invoice_payment_statuses');
	}
}
