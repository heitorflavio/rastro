<?php

namespace App\Services;

use App\Services\Exceptions\RouteServiceException;
use Illuminate\Support\Facades\Http;

class OsrmClient
{
    private const URL = 'https://router.project-osrm.org/table/v1/driving/';

    public function distanceMatrix(array $points): array
    {
        $pairs = array_map(
            fn (array $p) => $p['lon'].','.$p['lat'],
            $points,
        );

        $url = self::URL.implode(';', $pairs);

        $response = Http::timeout(20)
            ->acceptJson()
            ->get($url, ['annotations' => 'distance,duration']);

        $data = $response->json();

        if (($data['code'] ?? null) !== 'Ok' || ! isset($data['distances'], $data['durations'])) {
            throw new RouteServiceException(
                "OSRM não retornou rota válida (código: ".($data['code'] ?? 'desconhecido').')'
            );
        }

        return [
            'distances' => $data['distances'],
            'durations' => $data['durations'],
        ];
    }
}
