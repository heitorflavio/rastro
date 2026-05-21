<?php

namespace App\Services;

use App\Services\Exceptions\RouteServiceException;
use InvalidArgumentException;

class RouteOptimizer
{
    public function __construct(
        private readonly Geocoder $geocoder,
        private readonly OsrmClient $osrm,
    ) {}

    public function optimize(array $addresses): array
    {
        $addresses = array_values(array_filter(array_map('trim', $addresses), 'strlen'));

        if (count($addresses) < 3) {
            throw new InvalidArgumentException('Informe pelo menos 3 endereços (1 base + 2 paradas).');
        }

        if (count($addresses) > 25) {
            throw new InvalidArgumentException('Limite de 25 endereços por consulta (restrição da API pública).');
        }

        $coordinates = [];
        $resolvedNames = [];

        foreach ($addresses as $i => $address) {
            $geo = $this->geocoder->geocode($address);
            $coordinates[] = ['lat' => $geo['lat'], 'lon' => $geo['lon']];
            $resolvedNames[] = $geo['name'];

            if ($i < count($addresses) - 1 && ! app()->runningUnitTests()) {
                usleep(1_100_000); // Nominatim: máx. 1 req/s
            }
        }

        $matrix = $this->osrm->distanceMatrix($coordinates);
        $distances = $matrix['distances'];

        $aco = new AntColonyOptimizer($distances);
        $best = $aco->optimize();

        $route = $best['route'];
        $route[] = 0; // volta para base

        $originalCost = $this->routeCost($distances, range(0, count($distances) - 1));
        $savings = $originalCost > 0
            ? max(0, (1 - $best['cost'] / $originalCost) * 100)
            : 0;

        return [
            'route' => $route,
            'addresses' => $resolvedNames,
            'coordinates' => $coordinates,
            'cost_meters' => $best['cost'],
            'original_cost_meters' => $originalCost,
            'savings_percent' => $savings,
            'history' => $aco->history(),
            'google_maps_url' => $this->buildGoogleMapsUrl($route, $coordinates),
            'route_geometry' => $this->roadGeometry($route, $coordinates),
        ];
    }

    private function roadGeometry(array $route, array $coordinates): ?array
    {
        $ordered = array_map(fn (int $i) => $coordinates[$i], $route);

        try {
            return $this->osrm->route($ordered);
        } catch (RouteServiceException) {
            return null;
        }
    }

    private function routeCost(array $distances, array $route): float
    {
        $total = 0.0;
        $n = count($route);

        for ($i = 0; $i < $n; $i++) {
            $total += $distances[$route[$i]][$route[($i + 1) % $n]];
        }

        return $total;
    }

    private function buildGoogleMapsUrl(array $route, array $coordinates): string
    {
        $points = array_map(
            fn (int $i) => $coordinates[$i]['lat'].','.$coordinates[$i]['lon'],
            $route,
        );

        return 'https://www.google.com/maps/dir/'.implode('/', $points);
    }
}
