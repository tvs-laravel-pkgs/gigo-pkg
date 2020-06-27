<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WjorPartsAddPurchaseTypeCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wjor_parts', function (Blueprint $table) {
            $table->unsignedInteger('purchase_type')->after('part_id');
            $table->foreign("purchase_type")->references("id")->on("configs");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wjor_parts', function (Blueprint $table) {
            $table->dropForeign('wjor_parts_purchase_type_foreign');       
            $table->dropColumn('purchase_type');
        });
    }
}
