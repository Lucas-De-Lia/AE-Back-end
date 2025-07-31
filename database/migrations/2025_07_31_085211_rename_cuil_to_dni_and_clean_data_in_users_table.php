<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->renameColumn('cuil', 'dni');
        });
         DB::statement("
            UPDATE users
            SET dni = split_part(dni, '-', 2)
            WHERE dni LIKE '%-%-%'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->renameColumn('dni', 'cuil');
        });
        DB::statement("
            UPDATE users
            SET cuil = '20-' || cuil || '-4'
            WHERE cuil NOT LIKE '%-%-%'
        ");
    }
};