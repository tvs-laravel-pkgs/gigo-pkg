 <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SplitOrderTypesU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('split_order_types', function (Blueprint $table) {
			$table->unsignedInteger('paid_by_id')->nullable()->after('name');
			$table->foreign("paid_by_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("CASCADE");

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('split_order_types', function (Blueprint $table) {
			$table->dropForeign("split_order_types_paid_by_id_foreign");
			$table->dropColumn('paid_by_id');
		});
	}
}
