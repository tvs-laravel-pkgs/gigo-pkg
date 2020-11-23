<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableGatePassCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::create('gate_pass_customers', function (Blueprint $table) {
			$table->increments('id');
            $table->unsignedInteger('gate_pass_id');
			$table->unsignedInteger('customer_id')->nullable();
            $table->string('customer_name', 191)->nullable();
            $table->text('customer_address')->nullable();
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->timestamps();
			$table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	public function down() {
		Schema::dropIfExists('gate_pass_customers');
	}
}
