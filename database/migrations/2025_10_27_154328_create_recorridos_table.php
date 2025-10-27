<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::create('recorridos', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('api_id')->nullable();
        $table->uuid('ruta_id')->nullable();
        $table->uuid('vehiculo_id')->nullable();
        $table->uuid('perfil_id')->nullable();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->enum('estado', ['en_curso', 'finalizado'])->default('en_curso');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recorridos');
    }
};
