<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOnSiteVisitAddCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::table('on_site_orders', function (Blueprint $table) {
			$table->text('parts_requirements')->nullable()->after('se_remarks');
			$table->string('otp_no',10)->nullable()->after('parts_requirements');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('on_site_orders', function (Blueprint $table) {
			$table->dropColumn('parts_requirements');
			$table->dropColumn('otp_no');
		});
	}
}
