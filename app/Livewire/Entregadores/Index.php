<?php

namespace App\Livewire\Entregadores;

use App\Models\Entregador;
use App\Services\Exceptions\AddressNotFoundException;
use App\Services\Geocoder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Entregadores')]
#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate('required|string|min:2|max:120')]
    public string $nome = '';

    #[Validate('required|string|min:5|max:255')]
    public string $enderecoBase = '';

    #[Validate('required|numeric|min:0.1')]
    public float $pesoMaxKg = 0;

    #[Validate('required|numeric|min:0.1')]
    public float $volumeMaxLitros = 0;

    public function openForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->editingId = $id;

        if ($id) {
            $e = Entregador::findOrFail($id);
            $this->nome = $e->nome;
            $this->enderecoBase = $e->endereco_base;
            $this->pesoMaxKg = (float) $e->peso_max_kg;
            $this->volumeMaxLitros = (float) $e->volume_max_litros;
        } else {
            $this->nome = '';
            $this->enderecoBase = '';
            $this->pesoMaxKg = 0;
            $this->volumeMaxLitros = 0;
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function save(Geocoder $geocoder): void
    {
        $this->validate();

        $entregador = $this->editingId ? Entregador::findOrFail($this->editingId) : new Entregador;

        $data = [
            'nome' => $this->nome,
            'peso_max_kg' => $this->pesoMaxKg,
            'volume_max_litros' => $this->volumeMaxLitros,
            'endereco_base' => $this->enderecoBase,
        ];

        if (! $entregador->exists || $entregador->endereco_base !== $this->enderecoBase) {
            try {
                $geo = $geocoder->geocode($this->enderecoBase);
            } catch (AddressNotFoundException $e) {
                $this->addError('enderecoBase', $e->getMessage());

                return;
            }
            $data['lat_base'] = $geo['lat'];
            $data['lon_base'] = $geo['lon'];
        }

        $entregador->fill($data)->save();

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Entregador::whereKey($id)->delete();
    }

    public function render()
    {
        return view('livewire.entregadores.index', [
            'entregadores' => Entregador::orderBy('nome')->get(),
        ]);
    }
}
