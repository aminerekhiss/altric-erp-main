<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('vat_number', 50)->nullable()->after('tax_id');
            $table->string('trade_register_number', 100)->nullable()->after('vat_number');
            $table->string('rne_number', 100)->nullable()->after('trade_register_number');
            $table->boolean('tunisian_stamp_duty_enabled')->default(false)->after('rne_number');
            $table->bigInteger('tunisian_stamp_duty_amount')->default(1000)->after('tunisian_stamp_duty_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'vat_number',
                'trade_register_number',
                'rne_number',
                'tunisian_stamp_duty_enabled',
                'tunisian_stamp_duty_amount',
            ]);
        });
    }
};
