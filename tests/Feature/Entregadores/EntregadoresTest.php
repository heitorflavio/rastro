<?php

use App\Livewire\Entregadores\Index;
use App\Models\Entregador;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('exige autenticação para acessar a página', function () {
    auth()->logout();
    $this->get('/entregadores')->assertRedirect('/login');
});

test('rota /entregadores responde 200 para usuário logado', function () {
    $this->get('/entregadores')->assertOk();
});

test('lista entregadores existentes', function () {
    Entregador::factory()->create(['nome' => 'João Motoboy']);
    Entregador::factory()->create(['nome' => 'Maria Van']);

    Livewire::test(Index::class)
        ->assertSee('João Motoboy')
        ->assertSee('Maria Van');
});

test('cria entregador geocodificando o endereço base', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-19.92', 'lon' => '-43.93', 'display_name' => 'Praça Sete, BH'],
        ]),
    ]);

    Livewire::test(Index::class)
        ->call('openForm')
        ->set('nome', 'Carlos')
        ->set('enderecoBase', 'Praça Sete, BH')
        ->set('pesoMaxKg', 80)
        ->set('volumeMaxLitros', 150)
        ->call('save')
        ->assertHasNoErrors();

    $e = Entregador::first();
    expect($e->nome)->toBe('Carlos')
        ->and($e->lat_base)->toBe(-19.92)
        ->and($e->lon_base)->toBe(-43.93)
        ->and((float) $e->peso_max_kg)->toBe(80.0)
        ->and((float) $e->volume_max_litros)->toBe(150.0);
});

test('valida campos obrigatórios ao salvar', function () {
    Livewire::test(Index::class)
        ->call('openForm')
        ->set('nome', '')
        ->set('enderecoBase', '')
        ->set('pesoMaxKg', 0)
        ->set('volumeMaxLitros', 0)
        ->call('save')
        ->assertHasErrors(['nome', 'enderecoBase', 'pesoMaxKg', 'volumeMaxLitros']);
});

test('mostra erro quando endereço não é encontrado', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([]),
    ]);

    Livewire::test(Index::class)
        ->call('openForm')
        ->set('nome', 'Carlos')
        ->set('enderecoBase', 'endereço-impossível')
        ->set('pesoMaxKg', 80)
        ->set('volumeMaxLitros', 150)
        ->call('save')
        ->assertHasErrors('enderecoBase');

    expect(Entregador::count())->toBe(0);
});

test('edita um entregador existente sem regeocodificar quando endereço não mudou', function () {
    Http::fake(); // não deveria chamar Nominatim

    $e = Entregador::factory()->create(['nome' => 'Antigo', 'peso_max_kg' => 50]);

    Livewire::test(Index::class)
        ->call('openForm', $e->id)
        ->set('nome', 'Novo Nome')
        ->call('save')
        ->assertHasNoErrors();

    expect($e->refresh()->nome)->toBe('Novo Nome');
    Http::assertNothingSent();
});

test('exclui entregador', function () {
    $e = Entregador::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $e->id);

    expect(Entregador::find($e->id))->toBeNull();
});
