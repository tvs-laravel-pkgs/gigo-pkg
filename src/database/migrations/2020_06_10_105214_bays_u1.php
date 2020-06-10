<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BaysU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bays', function (Blueprint $table) {
            $table->unsignedInteger('area_type_id')->after('name')->nullable();

            $table->foreign("area_type_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bays', function (Blueprint $table) {
            $table->dropForeign('bays_area_type_id_foreign');
            $table->dropColumn("area_type_id");
        });

    }
}
