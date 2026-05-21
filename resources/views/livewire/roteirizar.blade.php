<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Roteirizar: {{ $entregador->nome }}</flux:heading>
            <flux:text class="text-zinc-500">{{ $entregador->endereco_base }}</flux:text>
        </div>
        <flux:button
            as="a"
            :href="route('entregadores.index')"
            variant="ghost"
            icon="arrow-left"
        >
            Voltar
        </flux:button>
    </div>

    <flux:card>
        <div class="flex flex-wrap gap-6">
            <div>
                <flux:text size="sm" class="text-zinc-500">Peso atribuído</flux:text>
                <flux:heading size="lg">
                    {{ number_format($entregador->pesoAtribuido(), 1, ',', '.') }} /
                    {{ number_format($entregador->peso_max_kg, 1, ',', '.') }} kg
                </flux:heading>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">Volume atribuído</flux:text>
                <flux:heading size="lg">
                    {{ number_format($entregador->volumeAtribuido(), 1, ',', '.') }} /
                    {{ number_format($entregador->volume_max_litros, 1, ',', '.') }} L
                </flux:heading>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">Entregas atribuídas</flux:text>
                <flux:heading size="lg">{{ $entregas->count() }}</flux:heading>
            </div>
            <div class="ml-auto">
                <flux:button
                    variant="primary"
                    icon="map"
                    wire:click="otimizar"
                    :disabled="$entregas->count() < 2"
                >
                    <span wire:loading.remove wire:target="otimizar">Otimizar rota</span>
                    <span wire:loading wire:target="otimizar">Otimizando…</span>
                </flux:button>
            </div>
        </div>
    </flux:card>

    @if ($mensagem)
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$mensagem" />
    @endif

    @if ($entregas->isEmpty())
        <flux:callout variant="warning" icon="information-circle"
            heading="Nenhuma entrega atribuída"
            text="Atribua entregas a este entregador na tela de Entregas antes de roteirizar."
        />
    @else
        <flux:card>
            <flux:heading size="lg" class="mb-3">Entregas {{ $resultado ? 'em ordem otimizada' : 'atribuídas' }}</flux:heading>
            <ol class="space-y-3">
                @foreach ($entregas as $i => $e)
                    <li class="flex items-start gap-3" wire:key="entrega-row-{{ $e->id }}">
                        <flux:badge color="blue" size="lg" class="shrink-0 w-10 justify-center">
                            {{ $e->ordem_na_rota ?? ($i + 1) }}
                        </flux:badge>
                        <div>
                            <div class="font-medium">{{ $e->endereco }}</div>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ number_format($e->peso_kg, 2, ',', '.') }} kg ·
                                {{ number_format($e->volume_litros, 2, ',', '.') }} L
                            </flux:text>
                        </div>
                    </li>
                @endforeach
            </ol>
        </flux:card>
    @endif

    @if ($resultado)
        @php
            $km = fn (float $m) => number_format($m / 1000, 2, ',', '.') . ' km';
        @endphp

        <flux:card>
            <div class="space-y-3">
                <flux:text class="text-zinc-500">Distância total otimizada</flux:text>
                <flux:heading size="xl" class="text-emerald-600 dark:text-emerald-400">
                    {{ $km($resultado['cost_meters']) }}
                </flux:heading>

                @if ($resultado['savings_percent'] > 0.5)
                    <flux:text class="text-emerald-600 dark:text-emerald-400">
                        Economia de {{ number_format($resultado['savings_percent'], 1, ',', '.') }}%
                        vs. ordem de cadastro ({{ $km($resultado['original_cost_meters']) }}).
                    </flux:text>
                @endif

                <div class="flex flex-wrap gap-2 pt-2">
                    <flux:button
                        as="a"
                        :href="$resultado['google_maps_url']"
                        target="_blank"
                        variant="primary"
                        icon="map-pin"
                    >
                        Abrir no Google Maps
                    </flux:button>

                    <flux:button
                        variant="ghost"
                        icon="clipboard"
                        x-data="{ url: @js($resultado['google_maps_url']) }"
                        x-on:click="
                            navigator.clipboard.writeText(url).then(() => {
                                $flux.toast({ text: 'Link copiado!', variant: 'success' });
                            });
                        "
                    >
                        Copiar link
                    </flux:button>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-3">Mapa da rota</flux:heading>
            <div
                wire:ignore
                x-data
                x-init="
                    const coords = @js($resultado['coordinates']);
                    const route = @js($resultado['route']);
                    const addresses = @js($resultado['addresses']);
                    const geometry = @js($resultado['route_geometry'] ?? null);

                    const initMap = () => {
                        if (typeof L === 'undefined') {
                            return setTimeout(initMap, 100);
                        }

                        if ($el._map) { $el._map.remove(); }

                        const map = L.map($el).setView([coords[0].lat, coords[0].lon], 13);
                        $el._map = map;

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap',
                        }).addTo(map);

                        // Polilinha: geometria real das ruas (OSRM /route) ou retas como fallback
                        const polylineLatLngs = geometry
                            ? geometry.map(p => [p.lat, p.lon])
                            : route.map(i => [coords[i].lat, coords[i].lon]);
                        L.polyline(polylineLatLngs, { color: '#10b981', weight: 4, opacity: 0.85 }).addTo(map);

                        const latLngs = route.map(i => [coords[i].lat, coords[i].lon]);

                        route.forEach((idx, pos) => {
                            const isBase = pos === 0 || pos === route.length - 1;
                            const label = isBase ? '🏠' : String(pos);
                            const icon = L.divIcon({
                                className: '',
                                html: `<div style=&quot;background:${isBase ? '#10b981' : '#3b82f6'};color:white;border-radius:9999px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:700;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.4)&quot;>${label}</div>`,
                                iconSize: [28, 28],
                                iconAnchor: [14, 14],
                            });
                            L.marker([coords[idx].lat, coords[idx].lon], { icon })
                                .bindPopup(`<b>${isBase ? 'Base' : 'Parada ' + pos}</b><br>${addresses[idx]}`)
                                .addTo(map);
                        });

                        map.fitBounds(L.latLngBounds(latLngs).pad(0.15));
                    };

                    initMap();
                "
                class="h-96 w-full rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700"
            ></div>
        </flux:card>
    @endif
</div>
