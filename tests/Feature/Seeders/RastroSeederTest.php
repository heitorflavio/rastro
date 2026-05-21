<?php

use App\Models\Entrega;
use App\Models\Entregador;
use Database\Seeders\RastroSeeder;
use Illuminate\Support\Facades\Http;

test('cria Heitor com 200 kg / 300 L e base geocodificada', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-16.71', 'lon' => '-43.86', 'display_name' => 'Endereço resolvido'],
        ]),
    ]);

    $this->seed(RastroSeeder::class);

    $heitor = Entregador::where('nome', 'Heitor')->first();

    expect($heitor)->not->toBeNull()
        ->and((float) $heitor->peso_max_kg)->toBe(200.0)
        ->and((float) $heitor->volume_max_litros)->toBe(300.0)
        ->and($heitor->endereco_base)->toContain('Rua Peroba')
        ->and($heitor->lat_base)->toBe(-16.71);
});

test('cria 8 entregas atribuídas e 2 pendentes', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-16.71', 'lon' => '-43.86', 'display_name' => 'X'],
        ]),
    ]);

    $this->seed(RastroSeeder::class);

    expect(Entrega::where('status', 'atribuida')->count())->toBe(8)
        ->and(Entrega::where('status', 'pendente')->count())->toBe(2)
        ->and(Entrega::count())->toBe(10);
});

test('soma de peso/volume das atribuídas cabe na capacidade do Heitor', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-16.71', 'lon' => '-43.86', 'display_name' => 'X'],
        ]),
    ]);

    $this->seed(RastroSeeder::class);

    $heitor = Entregador::where('nome', 'Heitor')->first();

    expect($heitor->pesoAtribuido())->toBeLessThanOrEqual($heitor->peso_max_kg)
        ->and($heitor->volumeAtribuido())->toBeLessThanOrEqual($heitor->volume_max_litros);
});

test('é idempotente — rodar duas vezes não duplica', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-16.71', 'lon' => '-43.86', 'display_name' => 'X'],
        ]),
    ]);

    $this->seed(RastroSeeder::class);
    $this->seed(RastroSeeder::class);

    expect(Entregador::where('nome', 'Heitor')->count())->toBe(1)
        ->and(Entrega::count())->toBe(10);
});
