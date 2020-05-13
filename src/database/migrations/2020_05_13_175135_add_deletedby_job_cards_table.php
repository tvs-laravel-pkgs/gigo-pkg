<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedbyJobCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        //
         Schema::table("job_cards", function ($table) {
            $table->unsignedInteger("deleted_by")->after('created_by')->nullable();
            $table->foreign("deleted_by")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        //
          Schema::table('job_cards', function(Blueprint $table)
        {
            $table->dropForeign('job_cards_deleted_by_foreign');
        });
    }
}
