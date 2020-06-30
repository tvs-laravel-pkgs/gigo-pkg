<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTblShortUrl extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('short_urls', function (Blueprint $table) {
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->timestamps();
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('short_urls', function (Blueprint $table) {
			$table->dropForeign("short_urls_updated_by_id_foreign");
			$table->dropColumn('updated_by_id');
			$table->dropTimestamps();
		});
	}
}
