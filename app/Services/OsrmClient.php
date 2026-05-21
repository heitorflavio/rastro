<?php

namespace App\Services;

use App\Services\Exceptions\RouteServiceException;
use Illuminate\Support\Facades\Http;

class OsrmClient
{
    private const TABLE_URL = 'https://router.project-osrm.org/table/v1/driving/';

    private const ROUTE_URL = 'https://router.project-osrm.org/route/v1/driving/';

    public function distanceMatrix(array $points): array
    {
        $url = self::TABLE_URL.$this->coordsToPath($points);

        $response = Http::timeout(20)
            ->acceptJson()
            ->get($url, ['annotations' => 'distance,duration']);

        $data = $response->json();

        if (($data['code'] ?? null) !== 'Ok' || ! isset($data['distances'], $data['durations'])) {
            throw new RouteServiceException(
                'OSRM não retornou rota válida (código: '.($data['code'] ?? 'desconhecido').')'
            );
        }

        return [
            'distances' => $data['distances'],
            'durations' => $data['durations'],
        ];
    }

    /**
     * Retorna a geometria da rota (sequência de pontos pelas ruas) entre os pontos
     * informados, na ordem dada. Cada item é ['lat' => float, 'lon' => float].
     */
    public function route(array $points): array
    {
        $url = self::ROUTE_URL.$this->coordsToPath($points);

        $response = Http::timeout(20)
            ->acceptJson()
            ->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
            ]);

        $data = $response->json();

        if (($data['code'] ?? null) !== 'Ok' || empty($data['routes'][0]['geometry']['coordinates'])) {
            throw new RouteServiceException(
                'OSRM não retornou rota válida (código: '.($data['code'] ?? 'desconhecido').')'
            );
        }

        return array_map(
            // GeoJSON usa [lon, lat] — convertemos para a convenção do app
            fn (array $pair) => ['lat' => (float) $pair[1], 'lon' => (float) $pair[0]],
            $data['routes'][0]['geometry']['coordinates'],
        );
    }

    private function coordsToPath(array $points): string
    {
        return implode(';', array_map(
            fn (array $p) => $p['lon'].','.$p['lat'],
            $points,
        ));
    }
}
