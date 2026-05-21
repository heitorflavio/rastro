<?php

use App\Livewire\Entregas\Index;
use App\Models\Entrega;
use App\Models\Entregador;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function fakeGeocoderOk(): void
{
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-19.92', 'lon' => '-43.93', 'display_name' => 'Endereço resolvido'],
        ]),
    ]);
}

test('exige autenticação para acessar a página', function () {
    auth()->logout();
    $this->get('/entregas')->assertRedirect('/login');
});

test('rota /entregas responde 200 para usuário logado', function () {
    $this->get('/entregas')->assertOk();
});

test('lista entregas existentes com endereço', function () {
    Entrega::factory()->create(['endereco' => 'Rua das Flores, 123']);

    Livewire::test(Index::class)->assertSee('Rua das Flores, 123');
});

test('cria entrega geocodificando o endereço (sem entregador)', function () {
    fakeGeocoderOk();

    Livewire::test(Index::class)
        ->call('openForm')
        ->set('endereco', 'Rua A')
        ->set('pesoKg', 5)
        ->set('volumeLitros', 10)
        ->set('entregadorId', null)
        ->call('save')
        ->assertHasNoErrors();

    $entrega = Entrega::first();
    expect($entrega->lat)->toBe(-19.92)
        ->and($entrega->lon)->toBe(-43.93)
        ->and($entrega->status)->toBe('pendente')
        ->and($entrega->entregador_id)->toBeNull();
});

test('cria entrega atribuindo a um entregador respeita capacidade e marca como atribuida', function () {
    fakeGeocoderOk();

    $entregador = Entregador::factory()->create(['peso_max_kg' => 100, 'volume_max_litros' => 200]);

    Livewire::test(Index::class)
        ->call('openForm')
        ->set('endereco', 'Rua A')
        ->set('pesoKg', 10)
        ->set('volumeLitros', 20)
        ->set('entregadorId', $entregador->id)
        ->call('save')
        ->assertHasNoErrors();

    $entrega = Entrega::first();
    expect($entrega->entregador_id)->toBe($entregador->id)
        ->and($entrega->status)->toBe('atribuida');
});

test('bloqueia criação quando entregador estoura capacidade de peso', function () {
    fakeGeocoderOk();

    $entregador = Entregador::factory()->create(['peso_max_kg' => 10, 'volume_max_litros' => 200]);
    Entrega::factory()->atribuida($entregador->id)->create(['peso_kg' => 8, 'volume_litros' => 5]);

    Livewire::test(Index::class)
        ->call('openForm')
        ->set('endereco', 'Rua A')
        ->set('pesoKg', 5) // 8 + 5 = 13 > 10
        ->set('volumeLitros', 10)
        ->set('entregadorId', $entregador->id)
        ->call('save')
        ->assertHasErrors('entregadorId');

    expect(Entrega::count())->toBe(1); // só a pré-existente
});

test('bloqueia criação quando entregador estoura capacidade de volume', function () {
    fakeGeocoderOk();

    $entregador = Entregador::factory()->create(['peso_max_kg' => 1000, 'volume_max_litros' => 10]);
    Entrega::factory()->atribuida($entregador->id)->create(['peso_kg' => 1, 'volume_litros' => 8]);

    Livewire::test(Index::class)
        ->call('openForm')
        ->set('endereco', 'Rua A')
        ->set('pesoKg', 1)
        ->set('volumeLitros', 5) // 8 + 5 = 13 > 10
        ->set('entregadorId', $entregador->id)
        ->call('save')
        ->assertHasErrors('entregadorId');
});

test('marca entrega como entregue setando timestamp', function () {
    $entregador = Entregador::factory()->create();
    $entrega = Entrega::factory()->atribuida($entregador->id)->create();

    Livewire::test(Index::class)
        ->call('marcarEntregue', $entrega->id);

    $entrega->refresh();
    expect($entrega->status)->toBe('entregue')
        ->and($entrega->entregue_em)->not->toBeNull();
});

test('exclui entrega', function () {
    $entrega = Entrega::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $entrega->id);

    expect(Entrega::find($entrega->id))->toBeNull();
});

test('filtra entregas por status', function () {
    Entrega::factory()->create(['status' => 'pendente', 'endereco' => 'Endereço Pendente']);
    Entrega::factory()->entregue()->create(['endereco' => 'Endereço Entregue']);

    Livewire::test(Index::class)
        ->set('filterStatus', 'pendente')
        ->assertSee('Endereço Pendente')
        ->assertDontSee('Endereço Entregue');
});
