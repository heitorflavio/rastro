<?php

namespace App\Livewire\Entregas;

use App\Models\Entrega;
use App\Models\Entregador;
use App\Services\Exceptions\AddressNotFoundException;
use App\Services\Geocoder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Entregas')]
#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate('required|string|min:5|max:255')]
    public string $endereco = '';

    #[Validate('required|numeric|min:0.01')]
    public float $pesoKg = 0;

    #[Validate('required|numeric|min:0.01')]
    public float $volumeLitros = 0;

    public ?int $entregadorId = null;

    public string $filterStatus = 'todas';

    public function openForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->editingId = $id;

        if ($id) {
            $e = Entrega::findOrFail($id);
            $this->endereco = $e->endereco;
            $this->pesoKg = (float) $e->peso_kg;
            $this->volumeLitros = (float) $e->volume_litros;
            $this->entregadorId = $e->entregador_id;
        } else {
            $this->endereco = '';
            $this->pesoKg = 0;
            $this->volumeLitros = 0;
            $this->entregadorId = null;
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

        $entrega = $this->editingId ? Entrega::findOrFail($this->editingId) : new Entrega;

        // Capacidade do entregador (se atribuído)
        if ($this->entregadorId) {
            $entregador = Entregador::find($this->entregadorId);
            if (! $entregador) {
                $this->addError('entregadorId', 'Entregador não encontrado.');

                return;
            }

            $pesoExistente = $entrega->exists && $entrega->entregador_id === $entregador->id
                ? (float) $entrega->peso_kg
                : 0;
            $volumeExistente = $entrega->exists && $entrega->entregador_id === $entregador->id
                ? (float) $entrega->volume_litros
                : 0;

            $delta = [
                'peso' => $this->pesoKg - $pesoExistente,
                'volume' => $this->volumeLitros - $volumeExistente,
            ];

            if (! $entregador->suportaCarga($delta['peso'], $delta['volume'])) {
                $this->addError('entregadorId', sprintf(
                    'Capacidade insuficiente: usado %.1f/%.1f kg, %.1f/%.1f L.',
                    $entregador->pesoAtribuido(),
                    $entregador->peso_max_kg,
                    $entregador->volumeAtribuido(),
                    $entregador->volume_max_litros,
                ));

                return;
            }
        }

        $data = [
            'endereco' => $this->endereco,
            'peso_kg' => $this->pesoKg,
            'volume_litros' => $this->volumeLitros,
            'entregador_id' => $this->entregadorId,
            'status' => $this->entregadorId ? Entrega::STATUS_ATRIBUIDA : Entrega::STATUS_PENDENTE,
        ];

        if (! $entrega->exists || $entrega->endereco !== $this->endereco) {
            try {
                $geo = $geocoder->geocode($this->endereco);
            } catch (AddressNotFoundException $e) {
                $this->addError('endereco', $e->getMessage());

                return;
            }
            $data['lat'] = $geo['lat'];
            $data['lon'] = $geo['lon'];
        }

        $entrega->fill($data)->save();

        $this->closeForm();
    }

    public function marcarEntregue(int $id): void
    {
        $entrega = Entrega::findOrFail($id);
        $entrega->update([
            'status' => Entrega::STATUS_ENTREGUE,
            'entregue_em' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        Entrega::whereKey($id)->delete();
    }

    public function render()
    {
        $query = Entrega::with('entregador')->latest();

        if ($this->filterStatus !== 'todas') {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.entregas.index', [
            'entregas' => $query->get(),
            'entregadores' => Entregador::orderBy('nome')->get(),
        ]);
    }
}
