<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSplitOrderTypeAddClaimableCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('split_order_types', function (Blueprint $table) {
            $table->unsignedtinyInteger('is_claimable')->default(0)->comment('0 => No 1 => Yes')	->after('name');
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
            $table->dropColumn('is_claimable');
		});
    }
}
