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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('invoice_file')->nullable()->after('logo');
            $table->date('invoice_date')->nullable()->after('invoice_file');
            $table->string('invoice_from')->nullable()->after('invoice_date');
            $table->string('new_product_name')->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_file',
                'invoice_date',
                'invoice_from',
                'new_product_name',
            ]);
        });
    }
};
