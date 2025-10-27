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
    Schema::table('posiciones', function (Blueprint $table) {
        $table->timestamps(); // crea created_at y updated_at
    });
}

public function down()
{
    Schema::table('posiciones', function (Blueprint $table) {
        $table->dropTimestamps();
    });
}

};
