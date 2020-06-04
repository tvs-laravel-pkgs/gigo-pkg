<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JobCardReturnableItemsU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('job_card_returnable_items', function (Blueprint $table) {
           $table->string('item_name', 191)->after("job_card_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_card_returnable_items', function (Blueprint $table) {
           $table->dropColumn("item_name");
        });
    }
}
