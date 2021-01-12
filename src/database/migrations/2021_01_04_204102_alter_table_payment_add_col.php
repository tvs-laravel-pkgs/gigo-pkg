<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePaymentAddCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('payments', function (Blueprint $table) {
			$table->string('payable_type', 200)->nullable()->after('entity_id');
			$table->unsignedInteger('payable_id')->nullable()->after('payable_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('payments', function (Blueprint $table) {
			$table->dropColumn('payable_type');
			$table->dropColumn('payable_id');
		});
	}
}
