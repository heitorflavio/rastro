<?php

namespace App\Services;

use App\Services\Exceptions\AddressNotFoundException;
use Illuminate\Support\Facades\Http;

class Geocoder
{
    private const URL = 'https://nominatim.openstreetmap.org/search';

    private const USER_AGENT = 'Rastro/1.0 (universidade)';

    public function geocode(string $address): array
    {
        $response = Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'application/json',
        ])
            ->timeout(20)
            ->get(self::URL, [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

        $data = $response->json();

        if (empty($data) || ! isset($data[0]['lat'])) {
            throw AddressNotFoundException::for($address);
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
            'name' => $data[0]['display_name'] ?? $address,
        ];
    }
}
