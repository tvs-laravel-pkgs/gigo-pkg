<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompaignPartC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('compaign_part')) {
			Schema::create('compaign_part', function (Blueprint $table) {

				$table->unsignedInteger('compaign_id');
				$table->unsignedInteger('part_id');

				$table->foreign("compaign_id")->references("id")->on("compaigns")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("part_id")->references("id")->on("parts")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(['compaign_id', 'part_id']);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('compaign_part');
	}
}
