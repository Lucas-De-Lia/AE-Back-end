<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //no pude instalar la dependencia que permite usar ->change()
        // 1. Convertir 'abstract' de news a tipo TEXT
        DB::statement('ALTER TABLE news ALTER COLUMN abstract TYPE TEXT');

        // 2. Hacer que news_id en pdf_files sea nullable
        DB::statement('ALTER TABLE pdf_files ALTER COLUMN news_id DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Revertir: cambiar abstract a VARCHAR(255)
        DB::statement('ALTER TABLE news ALTER COLUMN abstract TYPE VARCHAR(255)');

        // Revertir: volver a hacer news_id obligatorio
        DB::statement('ALTER TABLE pdf_files ALTER COLUMN news_id SET NOT NULL');
    }
};