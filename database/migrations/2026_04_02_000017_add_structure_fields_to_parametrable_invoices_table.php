<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametrable_invoices', function (Blueprint $table) {
            $table->boolean('is_structure')->default(false)->after('currency_code');
            $table->string('structure_name')->nullable()->after('is_structure');
        });
    }

    public function down(): void
    {
        Schema::table('parametrable_invoices', function (Blueprint $table) {
            $table->dropColumn(['is_structure', 'structure_name']);
        });
    }
};
