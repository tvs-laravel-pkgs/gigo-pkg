<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToJobCardReturnableItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_card_returnable_items', function (Blueprint $table) {
            $table->unsignedInteger('part_id')->nullable()->after('job_card_id');
            $table->foreign("part_id")->references("id")->on("parts")->onDelete("SET NULL")->onUpdate("CASCADE");
        });
    }
   /**
     * @return void
     */
    public function down()
    {
        Schema::table('job_card_returnable_items', function (Blueprint $table) {
            $table->dropForeign('job_card_returnable_items_part_id_foreign');
            $table->dropColumn('part_id');
        });
    }
}
