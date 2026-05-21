<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entregador_id')->nullable()->constrained('entregadores')->nullOnDelete();
            $table->string('endereco');
            $table->double('lat');
            $table->double('lon');
            $table->decimal('peso_kg', 10, 2);
            $table->decimal('volume_litros', 10, 2);
            $table->string('status')->default('pendente'); // pendente | atribuida | entregue
            $table->unsignedInteger('ordem_na_rota')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
