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
        Schema::table('news', function (Blueprint $table) {
            // Eliminamos columnas viejas
            $table->dropColumn(['title', 'abstract']);
            // Nuevos campos
            $table->string('titulo_principal', 255);
            $table->string('titulo_secundario', 255)->nullable();
            $table->text('texto_principal');
            $table->text('texto_secundario')->nullable();
            $table->integer('tiempo_lectura')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // Volvemos a lo anterior
            $table->string('title', 255);
            $table->text('abstract')->nullable();

            $table->dropColumn([
                'titulo_principal',
                'titulo_secundario',
                'texto_principal',
                'texto_secundario',
                'tiempo_lectura'
            ]);
        });
    }
};