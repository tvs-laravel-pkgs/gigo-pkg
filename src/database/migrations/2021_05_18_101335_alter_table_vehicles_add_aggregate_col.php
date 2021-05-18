<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVehiclesAddAggregateCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedTinyInteger('vehicle_type')->default(1)->after('company_id')->comment('1 -> Vehicle , 2 -> Aggregate vehicle');
            $table->string('aggregate_number', 40)->nullable()->after('registration_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('vehicle_type');
            $table->dropColumn('aggregate_number');
        });
    }
}
