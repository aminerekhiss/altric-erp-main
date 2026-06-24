<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametrable_invoices', function (Blueprint $table) {
            $table->string('print_logo')->nullable()->after('structure_name');
            $table->text('print_header')->nullable()->after('print_logo');
            $table->text('print_footer')->nullable()->after('print_header');
        });
    }

    public function down(): void
    {
        Schema::table('parametrable_invoices', function (Blueprint $table) {
            $table->dropColumn(['print_logo', 'print_header', 'print_footer']);
        });
    }
};
