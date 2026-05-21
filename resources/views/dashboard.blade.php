<x-layouts::app :title="__('Dashboard')">
    @php
        $totalEntregadores = \App\Models\Entregador::count();
        $pendentes = \App\Models\Entrega::where('status', 'pendente')->count();
        $atribuidas = \App\Models\Entrega::where('status', 'atribuida')->count();
        $entregues = \App\Models\Entrega::where('status', 'entregue')->count();
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <div>
            <flux:heading size="xl">Rastro</flux:heading>
            <flux:text class="text-zinc-500">Otimizador de rotas de entrega.</flux:text>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-4">
            <flux:card>
                <flux:text size="sm" class="text-zinc-500">Entregadores</flux:text>
                <flux:heading size="xl">{{ $totalEntregadores }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:text size="sm" class="text-zinc-500">Entregas pendentes</flux:text>
                <flux:heading size="xl" class="text-zinc-600 dark:text-zinc-300">{{ $pendentes }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:text size="sm" class="text-zinc-500">Entregas atribuídas</flux:text>
                <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">{{ $atribuidas }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:text size="sm" class="text-zinc-500">Entregas entregues</flux:text>
                <flux:heading size="xl" class="text-emerald-600 dark:text-emerald-400">{{ $entregues }}</flux:heading>
            </flux:card>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <flux:card class="flex flex-col gap-3">
                <flux:icon name="truck" class="size-8 text-blue-500" />
                <flux:heading size="lg">Entregadores</flux:heading>
                <flux:text class="text-zinc-500 grow">Cadastre motoboys, vans, drones — e a capacidade de carga de cada um.</flux:text>
                <flux:button as="a" :href="route('entregadores.index')" variant="primary" wire:navigate>
                    Abrir
                </flux:button>
            </flux:card>

            <flux:card class="flex flex-col gap-3">
                <flux:icon name="archive-box" class="size-8 text-emerald-500" />
                <flux:heading size="lg">Entregas</flux:heading>
                <flux:text class="text-zinc-500 grow">Registre as encomendas com endereço, peso e volume; atribua a um entregador respeitando capacidade.</flux:text>
                <flux:button as="a" :href="route('entregas.index')" variant="primary" wire:navigate>
                    Abrir
                </flux:button>
            </flux:card>

            <flux:card class="flex flex-col gap-3">
                <flux:icon name="map" class="size-8 text-purple-500" />
                <flux:heading size="lg">Otimizador manual</flux:heading>
                <flux:text class="text-zinc-500 grow">Cole uma lista de endereços e obtenha a melhor ordem sem cadastrar nada — útil pra testes rápidos.</flux:text>
                <flux:button as="a" :href="route('otimizador.manual')" variant="primary" wire:navigate>
                    Abrir
                </flux:button>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
