<?php

namespace App\Livewire;

use App\Actions\OtimizarRotaDoEntregador;
use App\Models\Entregador;
use App\Services\Exceptions\RouteServiceException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Roteirizar entregador')]
#[Layout('layouts.app')]
class Roteirizar extends Component
{
    public Entregador $entregador;

    public ?array $resultado = null;

    public ?string $mensagem = null;

    public function mount(Entregador $entregador): void
    {
        $this->entregador = $entregador;
    }

    public function otimizar(OtimizarRotaDoEntregador $action): void
    {
        $this->mensagem = null;
        $this->resultado = null;

        try {
            $resultado = $action->execute($this->entregador);
            // Não serializamos a collection de entregas no estado Livewire — só o que a view precisa.
            unset($resultado['entregas']);
            $this->resultado = $resultado;
        } catch (InvalidArgumentException|RouteServiceException $e) {
            $this->mensagem = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.roteirizar', [
            'entregas' => $this->entregador
                ->entregasAtribuidas()
                ->orderByRaw('ordem_na_rota IS NULL, ordem_na_rota ASC, id ASC')
                ->get(),
        ]);
    }
}
