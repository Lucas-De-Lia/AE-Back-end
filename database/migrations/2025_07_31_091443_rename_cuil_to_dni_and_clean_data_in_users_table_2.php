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
            // Eliminamos el índice único antiguo sobre 'cuil'
            $table->dropUnique('users_cuil_unique');
        });
        Schema::table('users', function (Blueprint $table) {
            //
            $table->renameColumn('cuil', 'dni');
        });
        DB::statement("
            UPDATE users
            SET dni = split_part(dni, '-', 2)
            WHERE dni LIKE '%-%-%'
        ");
        Schema::table('users', function (Blueprint $table) {
            // Agregamos la nueva restricción única en dni
            $table->unique('dni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminamos la restricción única sobre dni
            $table->dropUnique('users_dni_unique');
        });
        Schema::table('users', function (Blueprint $table) {
            //
            $table->renameColumn('dni', 'cuil');
        });
        DB::statement("
            UPDATE users
            SET cuil = '20-' || cuil || '-4'
            WHERE cuil NOT LIKE '%-%-%'
        ");
        Schema::table('users', function (Blueprint $table) {
            // Restauramos la restricción única original sobre cuil
            $table->unique('cuil');
        });
    }
};