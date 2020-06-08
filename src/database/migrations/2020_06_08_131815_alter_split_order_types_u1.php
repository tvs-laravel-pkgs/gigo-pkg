<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSplitOrderTypesU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('split_order_types', function (Blueprint $table) {
            $table->dropUnique('split_order_types_company_id_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('split_order_types', function (Blueprint $table) {
           $table->unique(["company_id", "name"]);
        });
    }
}
