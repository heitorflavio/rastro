<?php

use App\Services\Exceptions\AddressNotFoundException;
use App\Services\Geocoder;
use Illuminate\Support\Facades\Http;

test('geocodifica um endereço válido retornando lat/lon/nome', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            [
                'lat' => '-19.92',
                'lon' => '-43.93',
                'display_name' => 'Praça Sete de Setembro, Belo Horizonte, MG',
            ],
        ]),
    ]);

    $result = (new Geocoder)->geocode('Praça Sete, BH');

    expect($result)
        ->toMatchArray([
            'lat' => -19.92,
            'lon' => -43.93,
            'name' => 'Praça Sete de Setembro, Belo Horizonte, MG',
        ]);
});

test('lança AddressNotFoundException quando Nominatim devolve lista vazia', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([]),
    ]);

    (new Geocoder)->geocode('xyz-endereço-inexistente-987');
})->throws(AddressNotFoundException::class);

test('envia User-Agent customizado e o endereço como parâmetro q', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '0', 'lon' => '0', 'display_name' => 'X'],
        ]),
    ]);

    (new Geocoder)->geocode('Av. Paulista, 1000');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'q=')
            && str_contains($request->url(), 'format=json')
            && $request->hasHeader('User-Agent');
    });
});
