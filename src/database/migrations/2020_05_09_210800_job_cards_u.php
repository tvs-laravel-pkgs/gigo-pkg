<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobCardsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_cards', function (Blueprint $table) {
			$table->unsignedInteger('business_id')->nullable()->change();
			$table->unsignedInteger('job_order_id')->nullable()->after('segment_id');
			$table->string('order_number', 64)->nullable()->after('job_order_id');
			$table->unsignedInteger('floor_supervisor_id')->nullable()->after('order_number');
			$table->unsignedInteger('status_id')->nullable()->after('floor_supervisor_id');
			$table->unsignedInteger('bay_id')->nullable()->after('status_id');
			$table->string('otp_no', 25)->nullable()->after('bay_id');

			$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("floor_supervisor_id")->references("id")->on("users")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("bay_id")->references("id")->on("bays")->onDelete("CASCADE")->onUpdate("CASCADE");

			$table->unique(["job_order_id"]);
			$table->unique(["order_number"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_cards', function (Blueprint $table) {
			$table->unsignedInteger('business_id')->nullable(false)->change();
			$table->dropForeign('job_cards_job_order_id_foreign');
			$table->dropUnique('job_cards_job_order_id_unique');
			$table->dropColumn('job_order_id');
			$table->dropUnique('job_cards_order_number_unique');
			$table->dropColumn('order_number');
			$table->dropForeign('job_cards_floor_supervisor_id_foreign');
			$table->dropColumn('floor_supervisor_id');
			$table->dropForeign('job_cards_status_id_foreign');
			$table->dropColumn('status_id');
			$table->dropForeign('job_cards_bay_id_foreign');
			$table->dropColumn('bay_id');
			$table->dropColumn('otp_no');
		});
	}
}
