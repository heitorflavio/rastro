<?php

use App\Services\AntColonyOptimizer;

test('retorna rota começando em 0, sem repetir e visitando todos os pontos', function () {
    $dist = [
        [0, 1, 2, 1],
        [1, 0, 1, 2],
        [2, 1, 0, 1],
        [1, 2, 1, 0],
    ];

    $aco = new AntColonyOptimizer($dist, iterations: 50);
    $result = $aco->optimize();

    expect($result['route'])
        ->toBeArray()
        ->toHaveCount(4)
        ->and($result['route'][0])->toBe(0)
        ->and(array_unique($result['route']))->toHaveCount(4)
        ->and(min($result['route']))->toBe(0)
        ->and(max($result['route']))->toBe(3);
});

test('encontra rota ótima num quadrado de 4 cidades', function () {
    $s = sqrt(2);
    $dist = [
        [0, 1, $s, 1],
        [1, 0, 1, $s],
        [$s, 1, 0, 1],
        [1, $s, 1, 0],
    ];

    mt_srand(42);

    $aco = new AntColonyOptimizer($dist, iterations: 200);
    $result = $aco->optimize();

    // O tour ótimo segue pelas arestas do quadrado, custo = 4
    expect($result['cost'])->toEqualWithDelta(4.0, 0.0001);
});

test('custo nunca é pior que a ordem original (sem otimizar)', function () {
    $dist = [
        [0, 5, 9, 1],
        [5, 0, 3, 8],
        [9, 3, 0, 2],
        [1, 8, 2, 0],
    ];
    // Ordem original 0→1→2→3→0: 5+3+2+1 = 11

    $aco = new AntColonyOptimizer($dist, iterations: 100);
    $result = $aco->optimize();

    expect($result['cost'])->toBeLessThanOrEqual(11.0);
});

test('histórico tem uma entrada por iteração com melhor e média', function () {
    $dist = [[0, 1, 2], [1, 0, 1], [2, 1, 0]];

    $aco = new AntColonyOptimizer($dist, iterations: 7);
    $aco->optimize();

    expect($aco->history())
        ->toHaveCount(7)
        ->and($aco->history()[0])->toHaveKeys(['iteration', 'best', 'average']);
});
