<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCampaigns extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('compaigns', function (Blueprint $table) {
			$table->dropColumn('complaint_code');
			$table->dropColumn('fault_code');
		});

		Schema::table('compaigns', function (Blueprint $table) {
			$table->unsignedInteger('complaint_id')->nullable()->after('authorisation_no');
			$table->unsignedInteger('fault_id')->nullable()->after('complaint_id');
			$table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('fault_id')->references('id')->on('faults')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('compaigns', function (Blueprint $table) {
			$table->dropForeign('compaigns_complaint_id_foreign');
			$table->dropForeign('compaigns_fault_id_foreign');
			$table->dropColumn('complaint_id');
			$table->dropColumn('fault_id');
		});

		Schema::table('compaigns', function (Blueprint $table) {
			$table->string('complaint_code', 64)->nullable()->after('authorisation_no');
			$table->string('fault_code', 64)->nullable()->after('complaint_code');
		});
	}
}
