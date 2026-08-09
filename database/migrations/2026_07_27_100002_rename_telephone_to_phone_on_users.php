<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renomme telephone -> phone uniquement si l'ancien nom existe encore
        // (installations où la 1re migration a tourné avant le renommage).
        if (Schema::hasColumn('users', 'telephone') && ! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('telephone', 'phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'phone') && ! Schema::hasColumn('users', 'telephone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('phone', 'telephone');
            });
        }
    }
};
