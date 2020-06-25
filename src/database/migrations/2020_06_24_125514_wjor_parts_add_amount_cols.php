<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WjorPartsAddAmountCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wjor_parts', function (Blueprint $table) {
            $table->unsignedDecimal('quantity')->after('part_id');
            $table->unsignedDecimal('net_amount', 12, 2)->after('quantity');
            $table->unsignedDecimal('tax_total', 12, 2)->after('net_amount');
            $table->unsignedDecimal('total_amount', 12, 2)->after('tax_total');

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
            $table->dropColumn('net_amount');
            $table->dropColumn('tax_total');
            $table->dropColumn('total_amount');
        });
    }
}
