<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregadores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('endereco_base');
            $table->double('lat_base');
            $table->double('lon_base');
            $table->decimal('peso_max_kg', 10, 2);
            $table->decimal('volume_max_litros', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregadores');
    }
};
