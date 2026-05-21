<?php

use App\Services\Exceptions\RouteServiceException;
use App\Services\OsrmClient;
use Illuminate\Support\Facades\Http;

test('retorna matriz de distâncias e durações do OSRM', function () {
    Http::fake([
        'router.project-osrm.org/*' => Http::response([
            'code' => 'Ok',
            'distances' => [[0, 100], [100, 0]],
            'durations' => [[0, 10], [10, 0]],
        ]),
    ]);

    $points = [
        ['lat' => -19.92, 'lon' => -43.93],
        ['lat' => -19.91, 'lon' => -43.94],
    ];

    $matrix = (new OsrmClient)->distanceMatrix($points);

    expect($matrix)
        ->toMatchArray([
            'distances' => [[0, 100], [100, 0]],
            'durations' => [[0, 10], [10, 0]],
        ]);
});

test('lança RouteServiceException quando OSRM retorna código diferente de Ok', function () {
    Http::fake([
        'router.project-osrm.org/*' => Http::response([
            'code' => 'NoSegment',
        ]),
    ]);

    (new OsrmClient)->distanceMatrix([
        ['lat' => 0, 'lon' => 0],
        ['lat' => 1, 'lon' => 1],
    ]);
})->throws(RouteServiceException::class);

test('monta URL com lon,lat na ordem correta separados por ponto-e-vírgula', function () {
    Http::fake([
        'router.project-osrm.org/*' => Http::response([
            'code' => 'Ok',
            'distances' => [[0, 1], [1, 0]],
            'durations' => [[0, 1], [1, 0]],
        ]),
    ]);

    (new OsrmClient)->distanceMatrix([
        ['lat' => -19.92, 'lon' => -43.93],
        ['lat' => -19.91, 'lon' => -43.94],
    ]);

    Http::assertSent(function ($request) {
        // OSRM exige lon,lat na ordem
        return str_contains($request->url(), '-43.93,-19.92;-43.94,-19.91');
    });
});

test('route() retorna a geometria da rota como array de [lat, lon]', function () {
    Http::fake([
        'router.project-osrm.org/route/*' => Http::response([
            'code' => 'Ok',
            'routes' => [[
                'geometry' => [
                    'type' => 'LineString',
                    // OSRM GeoJSON: [lon, lat]
                    'coordinates' => [
                        [-43.93, -19.92],
                        [-43.935, -19.918],
                        [-43.94, -19.91],
                    ],
                ],
            ]],
        ]),
    ]);

    $geometry = (new OsrmClient)->route([
        ['lat' => -19.92, 'lon' => -43.93],
        ['lat' => -19.91, 'lon' => -43.94],
    ]);

    expect($geometry)->toBe([
        ['lat' => -19.92, 'lon' => -43.93],
        ['lat' => -19.918, 'lon' => -43.935],
        ['lat' => -19.91, 'lon' => -43.94],
    ]);
});

test('route() lança RouteServiceException quando OSRM retorna código diferente de Ok', function () {
    Http::fake([
        'router.project-osrm.org/route/*' => Http::response(['code' => 'NoRoute']),
    ]);

    (new OsrmClient)->route([
        ['lat' => 0, 'lon' => 0],
        ['lat' => 1, 'lon' => 1],
    ]);
})->throws(\App\Services\Exceptions\RouteServiceException::class);

test('route() pede overview=full e geometries=geojson', function () {
    Http::fake([
        'router.project-osrm.org/route/*' => Http::response([
            'code' => 'Ok',
            'routes' => [[
                'geometry' => ['type' => 'LineString', 'coordinates' => [[0, 0], [1, 1]]],
            ]],
        ]),
    ]);

    (new OsrmClient)->route([
        ['lat' => 0, 'lon' => 0],
        ['lat' => 1, 'lon' => 1],
    ]);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'overview=full')
        && str_contains($request->url(), 'geometries=geojson'));
});
