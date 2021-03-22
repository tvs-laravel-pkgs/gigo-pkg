<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableSiteVisit extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('on_site_orders')) {
			Schema::create('on_site_orders', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->unsignedInteger('outlet_id');
				$table->unsignedInteger('on_site_visit_user_id')->nullable();
				$table->string('number', 40);
				$table->unsignedInteger('customer_id')->nullable();
				$table->string('job_card_number', 40)->nullable();
				$table->unsignedInteger('service_type_id')->nullable();
				$table->date('planned_visit_date')->nullable();
				$table->date('actual_visit_date')->nullable();
				$table->text('customer_remarks')->nullable();
				$table->text('se_remarks')->nullable();
				$table->tinyInteger('is_customer_approved')->nullable()->comment('1 -> Yes, 0 -> No');
				$table->dateTime('customer_approved_date_time')->nullable();
				$table->unsignedInteger('status_id')->nullable();
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->unique('number');

				$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('on_site_visit_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('status_id')->references('id')->on('on_site_order_statuses')->onDelete('cascade')->onUpdate('cascade');

				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('on_site_orders');
	}
}
