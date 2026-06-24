<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametrable_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->nullable();
            $table->string('client_name')->nullable();
            $table->string('object')->nullable();
            $table->date('date')->nullable();
            $table->string('currency_code', 10)->default('TND');
            $table->decimal('total_ht', 15, 3)->default(0);
            $table->decimal('adjustments_total', 15, 3)->default(0);
            $table->decimal('net_ht', 15, 3)->default(0);
            $table->text('amount_in_words')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('parametrable_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parametrable_invoice_id')->constrained('parametrable_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('designation');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('puht', 15, 3)->default(0);
            $table->decimal('ptht', 15, 3)->default(0);
            $table->unsignedInteger('line_order')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('parametrable_invoice_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parametrable_invoice_id')->constrained('parametrable_invoices')->cascadeOnDelete();
            $table->string('label');
            $table->string('operation', 10)->default('add');
            $table->decimal('percentage', 8, 3)->default(0);
            $table->decimal('amount', 15, 3)->default(0);
            $table->unsignedInteger('sort_order')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametrable_invoice_adjustments');
        Schema::dropIfExists('parametrable_invoice_lines');
        Schema::dropIfExists('parametrable_invoices');
    }
};
