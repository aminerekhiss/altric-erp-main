<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->date('additional_cost_date')->nullable()->after('visite_amount');
            $table->decimal('additional_cost_amount', 15, 3)->nullable()->after('additional_cost_date');
            $table->string('additional_cost_note')->nullable()->after('additional_cost_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'additional_cost_date',
                'additional_cost_amount',
                'additional_cost_note',
            ]);
        });
    }
};
