<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Entregas</flux:heading>
            <flux:text class="text-zinc-500">Cadastre as encomendas e atribua aos entregadores.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openForm">
            Nova entrega
        </flux:button>
    </div>

    <flux:card>
        <div class="mb-4 flex items-center gap-3">
            <flux:select wire:model.live="filterStatus" size="sm" class="max-w-xs">
                <flux:select.option value="todas">Todas</flux:select.option>
                <flux:select.option value="pendente">Pendentes</flux:select.option>
                <flux:select.option value="atribuida">Atribuídas</flux:select.option>
                <flux:select.option value="entregue">Entregues</flux:select.option>
            </flux:select>
            <flux:text size="sm" class="text-zinc-500">{{ $entregas->count() }} entrega(s)</flux:text>
        </div>

        @if ($entregas->isEmpty())
            <flux:text class="text-zinc-500">Nenhuma entrega encontrada.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Endereço</flux:table.column>
                    <flux:table.column align="end">Peso</flux:table.column>
                    <flux:table.column align="end">Volume</flux:table.column>
                    <flux:table.column>Entregador</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Ações</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($entregas as $e)
                        <flux:table.row wire:key="entrega-{{ $e->id }}">
                            <flux:table.cell class="font-medium">{{ $e->endereco }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($e->peso_kg, 2, ',', '.') }} kg</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($e->volume_litros, 2, ',', '.') }} L</flux:table.cell>
                            <flux:table.cell class="text-zinc-500">{{ $e->entregador?->nome ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $color = match ($e->status) {
                                        'pendente' => 'zinc',
                                        'atribuida' => 'blue',
                                        'entregue' => 'emerald',
                                    };
                                @endphp
                                <flux:badge :color="$color" size="sm">{{ ucfirst($e->status) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button.group>
                                    @if ($e->status === 'atribuida')
                                        <flux:button
                                            size="sm"
                                            icon="check"
                                            wire:click="marcarEntregue({{ $e->id }})"
                                            title="Marcar como entregue"
                                        />
                                    @endif
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
                                        wire:confirm="Excluir esta entrega?"
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

    <flux:modal wire:model="showForm" class="md:w-[520px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar entrega' : 'Nova entrega' }}</flux:heading>

            <flux:input
                wire:model="endereco"
                label="Endereço de entrega"
                description="Será geocodificado pelo OpenStreetMap"
                required
                autofocus
            />

            <div class="grid grid-cols-2 gap-3">
                <flux:input
                    wire:model="pesoKg"
                    label="Peso (kg)"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                />
                <flux:input
                    wire:model="volumeLitros"
                    label="Volume (L)"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                />
            </div>

            <flux:select wire:model="entregadorId" label="Entregador (opcional)">
                <flux:select.option value="">— Não atribuir —</flux:select.option>
                @foreach ($entregadores as $ent)
                    <flux:select.option :value="$ent->id">
                        {{ $ent->nome }} ({{ number_format($ent->pesoAtribuido(), 1, ',', '.') }}/{{ number_format($ent->peso_max_kg, 1, ',', '.') }} kg,
                        {{ number_format($ent->volumeAtribuido(), 1, ',', '.') }}/{{ number_format($ent->volume_max_litros, 1, ',', '.') }} L)
                    </flux:select.option>
                @endforeach
            </flux:select>

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
