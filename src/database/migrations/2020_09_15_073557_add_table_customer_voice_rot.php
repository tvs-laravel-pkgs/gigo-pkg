<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCustomerVoiceRot extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('customer_voice_repair_orders', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('customer_voice_id');
			$table->unsignedInteger('repair_order_id');

			$table->unique(['customer_voice_id', 'repair_order_id'], 'voc_rot_id_unique');
			$table->foreign('customer_voice_id')->references('id')->on('customer_voices')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('repair_order_id')->references('id')->on('repair_orders')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('customer_voice_repair_orders');
	}
}
