<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRotIemDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('rot_iem_details')) {
            Schema::create('rot_iem_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id')->nullable();
                $table->unsignedInteger('business_id')->nullable();
                $table->string('rot_code', 30)->nullable();
                $table->string('job_group', 30)->nullable();
                $table->string('name', 100)->nullable();
                $table->integer('km')->nullable();
                $table->integer('man_days')->nullable();
                $table->decimal('working_hrs_start_time', 12, 2)->nullable();
                $table->decimal('working_hrs_close_time', 12, 2)->nullable();
                $table->decimal('total_working_hrs', 12, 2)->nullable();
                $table->decimal('onsite_price', 12, 2)->nullable();
                $table->decimal('rehab_price', 12, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('created_by_id')->nullable();
                $table->unsignedInteger('updated_by_id')->nullable();
                $table->unsignedInteger('deleted_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('SET NULL')->onUpdate('cascade');
                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('SET NULL')->onUpdate('cascade');

                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

                $table->index(['company_id', 'rot_code'], 'company_rot_index');
                $table->unique(['company_id', 'rot_code'], 'company_rot_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rot_iem_details');
    }
}
