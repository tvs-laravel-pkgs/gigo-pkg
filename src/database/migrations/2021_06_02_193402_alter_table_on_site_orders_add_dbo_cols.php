<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOnSiteOrdersAddDboCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('on_site_orders', function (Blueprint $table) {
            $table->unsignedInteger('address_id')->nullable()->after('notification_sent_status');
            $table->unsignedTinyInteger('e_invoice_registration')->after('status_id')->default(0)->comment('1 -> B2B , 0 -> B2C');
            $table->string('qr_image', 191)->nullable()->after('e_invoice_registration');
            $table->string('irn_number', 191)->nullable()->after('qr_image');
            $table->string('ack_no', 191)->nullable()->after('irn_number');
            $table->dateTime('ack_date')->nullable()->after('ack_no');
            $table->string('version', 191)->nullable()->after('ack_date');
            $table->text('irn_request')->nullable()->after('version');
            $table->text('irn_response')->nullable()->after('irn_request');
            $table->text('errors')->nullable()->after('irn_response');

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
        Schema::table('on_site_orders', function (Blueprint $table) {
            $table->dropForeign('on_site_orders_address_id_foreign');
            $table->dropColumn('address_id');
            $table->dropColumn('e_invoice_registration');
            $table->dropColumn('qr_image');
            $table->dropColumn('irn_number');
            $table->dropColumn('ack_no');
            $table->dropColumn('ack_date');
            $table->dropColumn('version');
            $table->dropColumn('irn_request');
            $table->dropColumn('irn_response');
            $table->dropColumn('errors');
        });
    }
}
