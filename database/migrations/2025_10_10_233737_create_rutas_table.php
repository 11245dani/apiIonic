<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->uuid('api_id')->nullable()->unique(); // ID remoto desde la API principal
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('perfil_id')->nullable();
            $table->string('nombre_ruta');
            $table->json('calles')->nullable(); // almacena los IDs de calles seleccionadas
            $table->json('shape')->nullable();  // opcional, forma geométrica
            $table->boolean('sincronizado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
