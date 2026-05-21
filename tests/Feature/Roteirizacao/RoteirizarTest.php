<?php

use App\Livewire\Roteirizar;
use App\Models\Entrega;
use App\Models\Entregador;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('exige autenticação para acessar a página', function () {
    auth()->logout();
    $entregador = Entregador::factory()->create();
    $this->get("/entregadores/{$entregador->id}/roteirizar")->assertRedirect('/login');
});

test('rota responde 200 e mostra dados do entregador', function () {
    $entregador = Entregador::factory()->create(['nome' => 'João Motoboy']);

    $this->get("/entregadores/{$entregador->id}/roteirizar")
        ->assertOk()
        ->assertSeeText('João Motoboy');
});

test('mostra entregas atribuídas ao entregador', function () {
    $entregador = Entregador::factory()->create();
    Entrega::factory()->atribuida($entregador->id)->create(['endereco' => 'Entrega A']);
    Entrega::factory()->atribuida($entregador->id)->create(['endereco' => 'Entrega B']);

    Livewire::test(Roteirizar::class, ['entregador' => $entregador])
        ->assertSee('Entrega A')
        ->assertSee('Entrega B');
});

test('exibe aviso quando há menos de 2 entregas atribuídas', function () {
    $entregador = Entregador::factory()->create();
    Entrega::factory()->atribuida($entregador->id)->create();

    Livewire::test(Roteirizar::class, ['entregador' => $entregador])
        ->call('otimizar')
        ->assertSet('mensagem', fn ($v) => is_string($v) && str_contains($v, 'pelo menos 2'));
});

test('otimiza rota e popula resultado, persistindo ordem_na_rota nas entregas', function () {
    Http::fake([
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

    $entregador = Entregador::factory()->create(['lat_base' => 0, 'lon_base' => 0]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 0, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 1]);
    Entrega::factory()->atribuida($entregador->id)->create(['lat' => 1, 'lon' => 0]);

    Livewire::test(Roteirizar::class, ['entregador' => $entregador])
        ->call('otimizar')
        ->assertSet('resultado.cost_meters', 4000.0)
        ->assertSeeText('4,00 km');

    expect(Entrega::whereNotNull('ordem_na_rota')->count())->toBe(3);
});
