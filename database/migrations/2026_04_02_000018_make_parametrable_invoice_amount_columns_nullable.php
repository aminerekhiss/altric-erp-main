<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametrable_invoice_lines', function (Blueprint $table) {
            $table->decimal('ptht', 15, 3)->nullable()->default(0)->change();
        });

        Schema::table('parametrable_invoice_adjustments', function (Blueprint $table) {
            $table->decimal('amount', 15, 3)->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('parametrable_invoice_lines', function (Blueprint $table) {
            $table->decimal('ptht', 15, 3)->default(0)->nullable(false)->change();
        });

        Schema::table('parametrable_invoice_adjustments', function (Blueprint $table) {
            $table->decimal('amount', 15, 3)->default(0)->nullable(false)->change();
        });
    }
};
