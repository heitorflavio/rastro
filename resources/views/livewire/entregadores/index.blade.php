<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Entregadores</flux:heading>
            <flux:text class="text-zinc-500">Cadastre seus entregadores e a capacidade de carga de cada um.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openForm">
            Novo entregador
        </flux:button>
    </div>

    <flux:card>
        @if ($entregadores->isEmpty())
            <flux:text class="text-zinc-500">Nenhum entregador cadastrado ainda.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Base</flux:table.column>
                    <flux:table.column align="end">Peso máx.</flux:table.column>
                    <flux:table.column align="end">Volume máx.</flux:table.column>
                    <flux:table.column align="end">Ações</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($entregadores as $e)
                        <flux:table.row wire:key="entregador-{{ $e->id }}">
                            <flux:table.cell class="font-medium">{{ $e->nome }}</flux:table.cell>
                            <flux:table.cell class="text-zinc-500">{{ $e->endereco_base }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($e->peso_max_kg, 1, ',', '.') }} kg</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($e->volume_max_litros, 1, ',', '.') }} L</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button.group>
                                    <flux:button
                                        size="sm"
                                        icon="map"
                                        as="a"
                                        :href="route('entregadores.roteirizar', $e)"
                                        title="Roteirizar"
                                    />
                                    <flux:button
                                        size="sm"
                                        icon="pencil"
                                        wire:click="openForm({{ $e->id }})"
                                        title="Editar"
                                    />
                                    <flux:button
                                        size="sm"
                                        icon="trash"
                                        wire:click="delete({{ $e->id }})"
                                        wire:confirm="Tem certeza? Esta ação não pode ser desfeita."
                                        title="Excluir"
                                    />
                                </flux:button.group>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal wire:model="showForm" class="md:w-[480px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar entregador' : 'Novo entregador' }}</flux:heading>

            <flux:input wire:model="nome" label="Nome" required autofocus />

            <flux:input
                wire:model="enderecoBase"
                label="Endereço base (saída e retorno)"
                description="Será geocodificado pelo OpenStreetMap"
                required
            />

            <div class="grid grid-cols-2 gap-3">
                <flux:input
                    wire:model="pesoMaxKg"
                    label="Peso máx. (kg)"
                    type="number"
                    step="0.1"
                    min="0.1"
                    required
                />
                <flux:input
                    wire:model="volumeMaxLitros"
                    label="Volume máx. (L)"
                    type="number"
                    step="0.1"
                    min="0.1"
                    required
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" type="button" wire:click="closeForm">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">
                    <span wire:loading.remove wire:target="save">Salvar</span>
                    <span wire:loading wire:target="save">Salvando…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
