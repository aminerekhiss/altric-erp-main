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
            $table->foreignId('client_id')->nullable()->after('new_product_name')->constrained('clients')->nullOnDelete();
            $table->string('invoice_folder')->nullable()->after('invoice_from');
            $table->text('invoice_description')->nullable()->after('invoice_folder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn([
                'invoice_folder',
                'invoice_description',
            ]);
        });
    }
};
