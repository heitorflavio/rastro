<?php

namespace Database\Factories;

use App\Models\Entrega;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entrega>
 */
class EntregaFactory extends Factory
{
    protected $model = Entrega::class;

    public function definition(): array
    {
        return [
            'entregador_id' => null,
            'endereco' => fake()->streetAddress().', Belo Horizonte, MG',
            'lat' => -19.92 + (mt_rand(-200, 200) / 10000),
            'lon' => -43.93 + (mt_rand(-200, 200) / 10000),
            'peso_kg' => fake()->randomFloat(2, 1, 20),
            'volume_litros' => fake()->randomFloat(2, 1, 30),
            'status' => Entrega::STATUS_PENDENTE,
        ];
    }

    public function atribuida(int $entregadorId): self
    {
        return $this->state(fn () => [
            'entregador_id' => $entregadorId,
            'status' => Entrega::STATUS_ATRIBUIDA,
        ]);
    }

    public function entregue(): self
    {
        return $this->state(fn () => [
            'status' => Entrega::STATUS_ENTREGUE,
            'entregue_em' => now(),
        ]);
    }
}
