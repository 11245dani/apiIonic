<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('calles', function (Blueprint $table) {
            $table->id();
            $table->uuid('api_id')->nullable()->index(); // id remoto si existe
            $table->string('nombre')->nullable();
            $table->string('barrio')->nullable();
            $table->json('meta')->nullable(); // raw payload
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calles');
    }
};
