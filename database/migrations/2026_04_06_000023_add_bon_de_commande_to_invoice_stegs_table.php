<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_stegs', function (Blueprint $table) {
            $table->string('bon_de_commande')->nullable()->after('object');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_stegs', function (Blueprint $table) {
            $table->dropColumn('bon_de_commande');
        });
    }
};
