<?php

use App\Livewire\RouteOptimizer;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::sequence()
            ->push([['lat' => '0', 'lon' => '0', 'display_name' => 'Base']])
            ->push([['lat' => '0', 'lon' => '1', 'display_name' => 'B']])
            ->push([['lat' => '1', 'lon' => '1', 'display_name' => 'C']])
            ->push([['lat' => '1', 'lon' => '0', 'display_name' => 'D']]),
        'router.project-osrm.org/*' => Http::response([
            'code' => 'Ok',
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

test('a página inicial responde com 200', function () {
    $this->get('/')->assertOk();
});

test('renderiza com 3 linhas de endereço vazias por padrão', function () {
    Livewire::test(RouteOptimizer::class)
        ->assertSee('Rastro')
        ->assertCount('addresses', 3);
});

test('addAddress adiciona uma nova linha vazia', function () {
    Livewire::test(RouteOptimizer::class)
        ->call('addAddress')
        ->assertCount('addresses', 4);
});

test('removeAddress remove a linha pelo índice', function () {
    Livewire::test(RouteOptimizer::class)
        ->set('addresses', ['A', 'B', 'C', 'D'])
        ->call('removeAddress', 1)
        ->assertSet('addresses', ['A', 'C', 'D']);
});

test('moveUp troca a linha com a anterior', function () {
    Livewire::test(RouteOptimizer::class)
        ->set('addresses', ['A', 'B', 'C'])
        ->call('moveUp', 2)
        ->assertSet('addresses', ['A', 'C', 'B']);
});

test('moveDown troca a linha com a próxima', function () {
    Livewire::test(RouteOptimizer::class)
        ->set('addresses', ['A', 'B', 'C'])
        ->call('moveDown', 0)
        ->assertSet('addresses', ['B', 'A', 'C']);
});

test('loadExample preenche com 5 endereços de exemplo', function () {
    Livewire::test(RouteOptimizer::class)
        ->call('loadExample')
        ->assertCount('addresses', 5);
});

test('clearAll volta ao estado inicial (3 linhas vazias, sem resultado)', function () {
    Livewire::test(RouteOptimizer::class)
        ->set('addresses', ['Base', 'B', 'C', 'D'])
        ->call('optimize')
        ->assertNotSet('result', null)
        ->call('clearAll')
        ->assertSet('addresses', ['', '', ''])
        ->assertSet('result', null);
});

test('exibe erro quando há menos de 3 endereços não-vazios', function () {
    Livewire::test(RouteOptimizer::class)
        ->set('addresses', ['Base', 'Apenas duas', ''])
        ->call('optimize')
        ->assertHasErrors('addresses');
});

test('otimiza rota e popula resultado quando recebe endereços válidos', function () {
    Livewire::test(RouteOptimizer::class)
        ->set('addresses', ['Base', 'B', 'C', 'D'])
        ->call('optimize')
        ->assertHasNoErrors()
        ->assertSet('result.cost_meters', 4000.0)
        ->assertSeeText('4,00 km');
});
