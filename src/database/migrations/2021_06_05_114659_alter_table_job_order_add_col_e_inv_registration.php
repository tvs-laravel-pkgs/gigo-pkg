<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderAddColEInvRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->unsignedInteger('address_id')->nullable()->after('customer_id');
            $table->unsignedTinyInteger('e_invoice_registration')->after('address_id')->default(0)->comment('1 -> B2B , 0 -> B2C');
            $table->string('qr_image', 191)->nullable()->after('status_id');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign('job_orders_address_id_foreign');
            $table->dropColumn('address_id');
            $table->dropColumn('e_invoice_registration');
            $table->dropColumn('qr_image');
        });
    }
}
