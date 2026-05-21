<?php

namespace App\Actions;

use App\Models\Entregador;
use App\Services\AntColonyOptimizer;
use App\Services\Exceptions\RouteServiceException;
use App\Services\OsrmClient;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OtimizarRotaDoEntregador
{
    public function __construct(private readonly OsrmClient $osrm) {}

    public function execute(Entregador $entregador): array
    {
        $entregas = $entregador->entregasAtribuidas()->orderBy('id')->get();

        if ($entregas->count() < 2) {
            throw new InvalidArgumentException(
                'Entregador precisa de pelo menos 2 entregas atribuídas para roteirizar.'
            );
        }

        $points = [['lat' => $entregador->lat_base, 'lon' => $entregador->lon_base]];
        foreach ($entregas as $entrega) {
            $points[] = ['lat' => $entrega->lat, 'lon' => $entrega->lon];
        }

        $matrix = $this->osrm->distanceMatrix($points);
        $aco = new AntColonyOptimizer($matrix['distances']);
        $best = $aco->optimize();

        $closedRoute = [...$best['route'], 0];

        DB::transaction(function () use ($entregas, $best) {
            foreach ($best['route'] as $position => $pointIndex) {
                if ($pointIndex === 0) {
                    continue; // base
                }
                $entregaOffset = $pointIndex - 1;
                $entregas[$entregaOffset]->update(['ordem_na_rota' => $position]);
            }
        });

        $originalCost = $this->custoRotaOrdemOriginal($matrix['distances']);
        $savings = $originalCost > 0
            ? max(0, (1 - $best['cost'] / $originalCost) * 100)
            : 0.0;

        $addresses = [
            $entregador->endereco_base,
            ...$entregas->pluck('endereco')->all(),
        ];

        return [
            'route' => $closedRoute,
            'addresses' => $addresses,
            'coordinates' => $points,
            'cost_meters' => $best['cost'],
            'original_cost_meters' => $originalCost,
            'savings_percent' => $savings,
            'history' => $aco->history(),
            'google_maps_url' => $this->urlGoogleMaps($closedRoute, $points),
            'route_geometry' => $this->geometriaPelasRuas($closedRoute, $points),
            'entregas' => $entregas->sortBy('ordem_na_rota')->values(),
        ];
    }

    /**
     * Busca a geometria real da rota pelas ruas no OSRM. Retorna null se a chamada falhar
     * (a view cai num fallback de linhas retas entre os pontos).
     */
    private function geometriaPelasRuas(array $closedRoute, array $points): ?array
    {
        $ordenados = array_map(fn (int $i) => $points[$i], $closedRoute);

        try {
            return $this->osrm->route($ordenados);
        } catch (RouteServiceException) {
            return null;
        }
    }

    private function custoRotaOrdemOriginal(array $distances): float
    {
        $n = count($distances);
        $total = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $total += $distances[$i][($i + 1) % $n];
        }

        return $total;
    }

    private function urlGoogleMaps(array $route, array $points): string
    {
        $pares = array_map(
            fn (int $i) => $points[$i]['lat'].','.$points[$i]['lon'],
            $route,
        );

        return 'https://www.google.com/maps/dir/'.implode('/', $pares);
    }
}
