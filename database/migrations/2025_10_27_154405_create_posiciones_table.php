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
    Schema::create('posiciones', function (Blueprint $table) {
        $table->id();
        $table->uuid('recorrido_id');
        $table->foreign('recorrido_id')->references('id')->on('recorridos')->onDelete('cascade');
        $table->decimal('latitud', 10, 7);
        $table->decimal('longitud', 10, 7);
        $table->timestamp('registrado_en')->useCurrent();
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posiciones');
    }
};
