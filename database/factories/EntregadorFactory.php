<?php

namespace Database\Factories;

use App\Models\Entregador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entregador>
 */
class EntregadorFactory extends Factory
{
    protected $model = Entregador::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'endereco_base' => fake()->streetAddress().', Belo Horizonte, MG',
            'lat_base' => -19.92 + (mt_rand(-100, 100) / 10000),
            'lon_base' => -43.93 + (mt_rand(-100, 100) / 10000),
            'peso_max_kg' => 100.0,
            'volume_max_litros' => 200.0,
        ];
    }
}
