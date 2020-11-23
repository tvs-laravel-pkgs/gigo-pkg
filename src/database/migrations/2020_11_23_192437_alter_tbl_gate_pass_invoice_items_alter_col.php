<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTblGatePassInvoiceItemsAlterCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gate_pass_invoice_items', function (Blueprint $table) {
          $table->unsignedDecimal('returned_qty',16,2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gate_pass_invoice_items', function (Blueprint $table) {
          $table->unsignedDecimal('returned_qty',16,2)->default(0)->change();
        });
    }
}
