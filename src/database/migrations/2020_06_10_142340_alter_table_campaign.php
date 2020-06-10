<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCampaign extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('compaigns', function (Blueprint $table) {
			$table->unsignedInteger('vehicle_model_id')->nullable()->after('claim_type_id');
			$table->date('manufacture_date')->nullable()->after('vehicle_model_id');
			$table->foreign('vehicle_model_id')->references('id')->on('models')->onDelete('cascade')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('compaigns', function (Blueprint $table) {
			$table->dropForeign('compaigns_vehicle_model_id_foreign');
			$table->dropColumn('vehicle_model_id');
			$table->dropColumn('manufacture_date');
		});
	}
}
