<?php

use App\Services\RouteOptimizer;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::sequence()
            ->push([['lat' => '0', 'lon' => '0', 'display_name' => 'Base']])
            ->push([['lat' => '0', 'lon' => '1', 'display_name' => 'Parada B']])
            ->push([['lat' => '1', 'lon' => '1', 'display_name' => 'Parada C']])
            ->push([['lat' => '1', 'lon' => '0', 'display_name' => 'Parada D']]),
        'router.project-osrm.org/*' => Http::response([
            'code' => 'Ok',
            // Quadrado: A(0,0) B(0,1) C(1,1) D(1,0)
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
});

test('otimiza rota com 4 endereços e retorna estrutura completa', function () {
    $result = app(RouteOptimizer::class)->optimize([
        'Base',
        'Parada B',
        'Parada C',
        'Parada D',
    ]);

    expect($result)->toHaveKeys([
        'route',          // [0, ..., 0]
        'addresses',      // nomes resolvidos
        'coordinates',    // lat/lon
        'cost_meters',    // float
        'original_cost_meters',
        'savings_percent',
        'history',
        'google_maps_url',
    ]);

    expect($result['route'])
        ->toHaveCount(5)                                     // 4 paradas + retorno à base
        ->and($result['route'][0])->toBe(0)
        ->and($result['route'][4])->toBe(0)
        ->and($result['cost_meters'])->toEqualWithDelta(4000.0, 0.01)
        ->and($result['google_maps_url'])->toStartWith('https://www.google.com/maps/dir/');
});

test('lança exceção quando há menos de 3 endereços', function () {
    app(RouteOptimizer::class)->optimize(['Base', 'B']);
})->throws(InvalidArgumentException::class, 'Informe pelo menos 3 endereços');

test('lança exceção quando há mais de 25 endereços', function () {
    app(RouteOptimizer::class)->optimize(array_fill(0, 26, 'X'));
})->throws(InvalidArgumentException::class, 'Limite de 25 endereços');
