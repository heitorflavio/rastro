<?php

namespace App\Livewire;

use App\Services\Exceptions\AddressNotFoundException;
use App\Services\Exceptions\RouteServiceException;
use App\Services\RouteOptimizer as RouteOptimizerService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Otimizador manual')]
#[Layout('layouts.app')]
class RouteOptimizer extends Component
{
    public array $addresses = ['', '', ''];

    public ?array $result = null;

    public ?string $errorMessage = null;

    private const EXAMPLE = [
        'Praça Sete de Setembro, Belo Horizonte, MG',
        'Mercado Central, Belo Horizonte, MG',
        'Parque Municipal, Belo Horizonte, MG',
        'Estádio Mineirão, Belo Horizonte, MG',
        'Shopping Del Rey, Belo Horizonte, MG',
    ];

    public function addAddress(): void
    {
        $this->addresses[] = '';
    }

    public function removeAddress(int $index): void
    {
        if (! isset($this->addresses[$index])) {
            return;
        }
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->addresses[$index - 1])) {
            return;
        }
        [$this->addresses[$index - 1], $this->addresses[$index]] =
            [$this->addresses[$index], $this->addresses[$index - 1]];
    }

    public function moveDown(int $index): void
    {
        if (! isset($this->addresses[$index + 1])) {
            return;
        }
        [$this->addresses[$index], $this->addresses[$index + 1]] =
            [$this->addresses[$index + 1], $this->addresses[$index]];
    }

    public function loadExample(): void
    {
        $this->addresses = self::EXAMPLE;
        $this->result = null;
        $this->errorMessage = null;
    }

    public function clearAll(): void
    {
        $this->addresses = ['', '', ''];
        $this->result = null;
        $this->errorMessage = null;
        $this->resetErrorBag();
    }

    public function optimize(RouteOptimizerService $optimizer): void
    {
        $this->errorMessage = null;
        $this->result = null;
        $this->resetErrorBag();

        $clean = array_values(array_filter(
            array_map('trim', $this->addresses),
            'strlen',
        ));

        if (count($clean) < 3) {
            $this->addError('addresses', 'Informe pelo menos 3 endereços (1 base + 2 paradas).');

            return;
        }

        if (count($clean) > 25) {
            $this->addError('addresses', 'Limite de 25 endereços por consulta.');

            return;
        }

        try {
            $this->result = $optimizer->optimize($clean);
        } catch (AddressNotFoundException|RouteServiceException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.route-optimizer');
    }
}
