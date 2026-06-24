<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_stegs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_city', 120)->default('Sfax');
            $table->date('date')->nullable();
            $table->string('client_name')->nullable();
            $table->text('client_address')->nullable();
            $table->string('object')->nullable();
            $table->string('currency_code', 10)->default('TND');
            $table->decimal('total_ht', 15, 3)->default(0);
            $table->decimal('tva_19', 15, 3)->default(0);
            $table->decimal('rg_5', 15, 3)->default(0);
            $table->decimal('total_ttc', 15, 3)->default(0);
            $table->decimal('retenue_source_1', 15, 3)->default(0);
            $table->decimal('tva_25', 15, 3)->default(0);
            $table->decimal('net_a_payer', 15, 3)->default(0);
            $table->text('amount_in_words')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_steg_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_steg_id')->constrained('invoice_stegs')->cascadeOnDelete();
            $table->string('code', 100)->nullable();
            $table->string('designation');
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('puht', 15, 3)->default(0);
            $table->decimal('ptht', 15, 3)->default(0);
            $table->unsignedInteger('line_order')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_steg_lines');
        Schema::dropIfExists('invoice_stegs');
    }
};
