<?php

use App\Actions\OtimizarRotaDoEntregador;
use App\Models\Entrega;
use App\Models\Entregador;
use Illuminate\Support\Facades\Http;

function fakeOsrmMatriz4x4(): void
{
    Http::fake([
        'router.project-osrm.org/*' => Http::response([
            'code' => 'Ok',
            'distances' => [
                [0, 1000, 1414, 1000],
                [1000, 0, 1000, 1414],
                [1414, 1000, 0, 1000],
                [1000, 1414, 1000, 0],
            ],
            'durations' => [
                [0, 60, 90, 60],
                [60, 0, 60, 90],
                [90, 60, 0, 60],
                [60, 90, 60, 0],
            ],
        ]),
    ]);
}

function fakeOsrmMatriz3x3(): void
{
    Http::fake([
        'router.project-osrm.org/*' => Http::response([
            'code' => 'Ok',
            'distances' => [[0, 1000, 1414], [1000, 0, 1000], [1414, 1000, 0]],
            'durations' => [[0, 60, 90], [60, 0, 60], [90, 60, 0]],
        ]),
    ]);
}

test('otimiza rota com 3 entregas atribuídas e retorna estrutura completa', function () {
    fakeOsrmMatriz4x4();

    $entregador = Entregador::factory()->create([
        'lat_base' => 0, 'lon_base' => 0, 'endereco_base' => 'Base',
    ]);

    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 0, 'lon' => 1, 'endereco' => 'A']);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 1, 'endereco' => 'B']);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 0, 'endereco' => 'C']);

    $result = app(OtimizarRotaDoEntregador::class)->execute($entregador);

    expect($result)->toHaveKeys([
        'route', 'addresses', 'coordinates',
        'cost_meters', 'original_cost_meters', 'savings_percent',
        'history', 'google_maps_url',
    ]);

    expect($result['route'][0])->toBe(0);
    expect($result['route'][count($result['route']) - 1])->toBe(0);
    expect($result['cost_meters'])->toEqualWithDelta(4000.0, 0.01);
    expect($result['google_maps_url'])->toStartWith('https://www.google.com/maps/dir/');
});

test('persiste ordem_na_rota em cada entrega atribuída', function () {
    fakeOsrmMatriz4x4();

    $entregador = Entregador::factory()->create(['lat_base' => 0, 'lon_base' => 0]);
    $a = Entrega::factory()->atribuida($entregador->id)->create(['lat' => 0, 'lon' => 1]);
    $b = Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 1]);
    $c = Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 0]);

    app(OtimizarRotaDoEntregador::class)->execute($entregador);

    $ordens = collect([$a, $b, $c])->map->refresh()->pluck('ordem_na_rota')->sort()->values()->all();
    expect($ordens)->toBe([1, 2, 3]);
});

test('ignora entregas com status diferente de atribuida', function () {
    fakeOsrmMatriz3x3();

    $entregador = Entregador::factory()->create(['lat_base' => 0, 'lon_base' => 0]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 0, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 1]);
    Entrega::factory()->create(['entregador_id' => $entregador->id, 'status' => 'pendente']);
    Entrega::factory()->entregue()->create(['entregador_id' => $entregador->id]);

    $result = app(OtimizarRotaDoEntregador::class)->execute($entregador);

    expect($result['coordinates'])->toHaveCount(3);
});

test('lança exceção quando entregador tem menos de 2 entregas atribuídas', function () {
    $entregador = Entregador::factory()->create();
    Entrega::factory()->atribuida($entregador->id)->create();

    app(OtimizarRotaDoEntregador::class)->execute($entregador);
})->throws(InvalidArgumentException::class);

test('inclui route_geometry quando OSRM /route responde', function () {
    Http::fake([
        'router.project-osrm.org/table/*' => Http::response([
            'code' => 'Ok',
            'distances' => [
                [0, 1000, 1414, 1000],
                [1000, 0, 1000, 1414],
                [1414, 1000, 0, 1000],
                [1000, 1414, 1000, 0],
            ],
            'durations' => [
                [0, 60, 90, 60],
                [60, 0, 60, 90],
                [90, 60, 0, 60],
                [60, 90, 60, 0],
            ],
        ]),
        'router.project-osrm.org/route/*' => Http::response([
            'code' => 'Ok',
            'routes' => [[
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [0, 0], [0.5, 0.5], [1, 1], [1, 0], [0, 0],
                    ],
                ],
            ]],
        ]),
    ]);

    $entregador = Entregador::factory()->create(['lat_base' => 0, 'lon_base' => 0]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 0, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 0]);

    $result = app(OtimizarRotaDoEntregador::class)->execute($entregador);

    expect($result['route_geometry'])
        ->toBeArray()
        ->toHaveCount(5)
        ->and($result['route_geometry'][0])->toMatchArray(['lat' => 0, 'lon' => 0]);
});

test('cai em route_geometry null quando OSRM /route falha (graceful fallback)', function () {
    Http::fake([
        'router.project-osrm.org/table/*' => Http::response([
            'code' => 'Ok',
            'distances' => [
                [0, 1000, 1414, 1000],
                [1000, 0, 1000, 1414],
                [1414, 1000, 0, 1000],
                [1000, 1414, 1000, 0],
            ],
            'durations' => [
                [0, 60, 90, 60],
                [60, 0, 60, 90],
                [90, 60, 0, 60],
                [60, 90, 60, 0],
            ],
        ]),
        'router.project-osrm.org/route/*' => Http::response(['code' => 'NoRoute']),
    ]);

    $entregador = Entregador::factory()->create(['lat_base' => 0, 'lon_base' => 0]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 0, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 0]);

    $result = app(OtimizarRotaDoEntregador::class)->execute($entregador);

    expect($result)->toHaveKey('route_geometry')
        ->and($result['route_geometry'])->toBeNull();
});
