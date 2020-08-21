<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthDateWarrantyJobOrderRequest extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {

			$table->dateTime('authorization_date')->after('authorization_number')->nullable();
			$table->unsignedInteger('authorization_by')->after('authorization_date')->nullable();
			$table->foreign('authorization_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {

			$table->dropColumn([
				'authorization_date',
				'authorization_by',
			]);

		});
	}
}
