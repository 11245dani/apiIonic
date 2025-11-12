<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->uuid('api_id')->nullable()->index();     // id remoto (API principal)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->uuid('perfil_id')->nullable()->index();
            $table->string('placa')->unique()->index();
            $table->string('marca');
            $table->string('modelo');
            $table->decimal('capacidad', 8, 2)->nullable();
            $table->string('tipo_combustible')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('sincronizado')->default(false);
            $table->timestamp('api_created_at')->nullable();
            $table->timestamp('api_updated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Clave foránea
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehiculos');
    }
};