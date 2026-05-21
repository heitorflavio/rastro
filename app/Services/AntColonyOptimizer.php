<?php

namespace App\Services;

class AntColonyOptimizer
{
    private array $pheromone;

    private int $n;

    private int $ants;

    private array $historyLog = [];

    private array $bestRoute = [];

    private float $bestCost = PHP_FLOAT_MAX;

    public function __construct(
        private readonly array $distances,
        ?int $ants = null,
        private readonly int $iterations = 120,
        private readonly float $alpha = 1.0,
        private readonly float $beta = 3.0,
        private readonly float $rho = 0.4,
        private readonly float $q = 1000.0,
    ) {
        $this->n = count($distances);
        $this->ants = $ants ?? max(10, $this->n);
        $this->pheromone = array_fill(0, $this->n, array_fill(0, $this->n, 1.0));
    }

    public function optimize(): array
    {
        for ($it = 1; $it <= $this->iterations; $it++) {
            $routes = [];
            $costs = [];

            for ($a = 0; $a < $this->ants; $a++) {
                $route = $this->buildRoute();
                $cost = $this->routeCost($route);
                $routes[] = $route;
                $costs[] = $cost;

                if ($cost < $this->bestCost) {
                    $this->bestCost = $cost;
                    $this->bestRoute = $route;
                }
            }

            $this->updatePheromone($routes, $costs);

            $this->historyLog[] = [
                'iteration' => $it,
                'best' => $this->bestCost,
                'average' => array_sum($costs) / count($costs),
            ];
        }

        return [
            'route' => $this->bestRoute,
            'cost' => $this->bestCost,
        ];
    }

    public function history(): array
    {
        return $this->historyLog;
    }

    private function buildRoute(): array
    {
        $route = [0];
        $remaining = range(1, $this->n - 1);

        while ($remaining) {
            $next = $this->pickNext(end($route), array_values($remaining));
            $route[] = $next;
            $remaining = array_values(array_diff($remaining, [$next]));
        }

        return $route;
    }

    private function pickNext(int $current, array $candidates): int
    {
        $weights = [];
        $sum = 0.0;

        foreach ($candidates as $c) {
            $d = $this->distances[$current][$c];
            if ($d <= 0) {
                $d = 0.0001;
            }
            $tau = $this->pheromone[$current][$c] ** $this->alpha;
            $eta = (1.0 / $d) ** $this->beta;
            $w = $tau * $eta;
            $weights[$c] = $w;
            $sum += $w;
        }

        if ($sum <= 0) {
            return $candidates[array_rand($candidates)];
        }

        $pick = (mt_rand() / mt_getrandmax()) * $sum;
        $acc = 0.0;

        foreach ($weights as $city => $w) {
            $acc += $w;
            if ($pick <= $acc) {
                return $city;
            }
        }

        return array_key_last($weights);
    }

    private function routeCost(array $route): float
    {
        $total = 0.0;
        $m = count($route);

        for ($i = 0; $i < $m; $i++) {
            $total += $this->distances[$route[$i]][$route[($i + 1) % $m]];
        }

        return $total;
    }

    private function updatePheromone(array $routes, array $costs): void
    {
        for ($i = 0; $i < $this->n; $i++) {
            for ($j = 0; $j < $this->n; $j++) {
                $this->pheromone[$i][$j] *= (1 - $this->rho);
            }
        }

        foreach ($routes as $k => $route) {
            $delta = $this->q / max($costs[$k], 0.0001);
            $m = count($route);
            for ($i = 0; $i < $m; $i++) {
                $a = $route[$i];
                $b = $route[($i + 1) % $m];
                $this->pheromone[$a][$b] += $delta;
                $this->pheromone[$b][$a] += $delta;
            }
        }

        if ($this->bestRoute) {
            $delta = $this->q / max($this->bestCost, 0.0001);
            $m = count($this->bestRoute);
            for ($i = 0; $i < $m; $i++) {
                $a = $this->bestRoute[$i];
                $b = $this->bestRoute[($i + 1) % $m];
                $this->pheromone[$a][$b] += $delta;
                $this->pheromone[$b][$a] += $delta;
            }
        }
    }
}
