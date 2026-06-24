<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->date('assurance_date')->nullable()->after('mission_date');
            $table->decimal('assurance_amount', 15, 3)->nullable()->after('assurance_date');
            $table->date('vignette_date')->nullable()->after('assurance_amount');
            $table->decimal('vignette_amount', 15, 3)->nullable()->after('vignette_date');
            $table->date('visite_date')->nullable()->after('vignette_amount');
            $table->decimal('visite_amount', 15, 3)->nullable()->after('visite_date');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'assurance_date',
                'assurance_amount',
                'vignette_date',
                'vignette_amount',
                'visite_date',
                'visite_amount',
            ]);
        });
    }
};
