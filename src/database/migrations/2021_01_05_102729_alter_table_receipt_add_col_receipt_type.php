<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableReceiptAddColReceiptType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receiptable_type',200)->nullable()->after('date');
            $table->unsignedInteger('receiptable_id')->nullable()->after('receiptable_type');
            $table->string('receipt_of_name',200)->nullable()->after('receiptable_id');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('receiptable_type');
            $table->dropColumn('receiptable_id');
            $table->dropColumn('receipt_of_name');
		});
    }
}
