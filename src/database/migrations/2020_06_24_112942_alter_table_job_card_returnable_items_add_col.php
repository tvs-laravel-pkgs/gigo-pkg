<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobCardReturnableItemsAddCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_card_returnable_items', function (Blueprint $table) {
			$table->dropForeign('job_card_returnable_items_job_card_id_foreign');
			$table->dropUnique('job_card_returnable_items_job_card_id_item_serial_no_unique');
			$table->foreign("job_card_id")->references("id")->on("job_cards")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->unique(['job_card_id', 'item_serial_no', 'item_name'], 'job_card_returnable_items_job_card_id_item_serial_name_unique');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_card_returnable_items', function (Blueprint $table) {
			$table->dropForeign('job_card_returnable_items_job_card_id_foreign');
			$table->dropUnique('job_card_returnable_items_job_card_id_item_serial_name_unique');
			$table->foreign("job_card_id")->references("id")->on("job_cards")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->unique(['job_card_id', 'item_serial_no']);
		});
	}
}
