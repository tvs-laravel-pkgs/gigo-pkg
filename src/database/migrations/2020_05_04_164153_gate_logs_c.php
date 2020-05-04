<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GateLogsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gate_logs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->string('number', 191);
			$table->datetime('gate_in_date');
			$table->string('driver_name', 191)->nullable();
			$table->string('contact_number', 191)->nullable();
			$table->unsignedInteger('vehicle_id')->nullable();
			$table->unsignedDecimal('km_reading', 10, 2)->nullable();
			$table->unsignedInteger('reading_type_id')->nullable();
			$table->string('gate_in_remarks', 191)->nullable();
			$table->datetime('gate_out_date')->nullable();
			$table->string('gate_out_remarks', 191)->nullable();
			$table->unsignedInteger('gate_pass_id')->nullable();
			$table->unsignedInteger('status_id')->nullable();
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('reading_type_id')->references('id')->on('configs')->onDelete('set null')->onUpdate('cascade');

			$table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

			$table->unique(["company_id", "number"]);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gate_logs');
	}
}
