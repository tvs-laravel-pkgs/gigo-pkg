<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PartServiceTypeC extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('part_service_type')) {
            Schema::create('part_service_type', function (Blueprint $table) {

                $table->unsignedInteger('part_id');
                $table->unsignedInteger('service_type_id');

                $table->foreign("part_id")->references("id")->on("parts")->onDelete("CASCADE")->onUpdate("CASCADE");
                $table->foreign("service_type_id")->references("id")->on("service_types")->onDelete("CASCADE")->onUpdate("CASCADE");

                $table->unique(['part_id', 'service_type_id']);
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
        Schema::dropIfExists('part_service_type');
    }
}
