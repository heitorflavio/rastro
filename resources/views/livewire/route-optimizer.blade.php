<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="space-y-1">
        <flux:heading size="xl">Rastro — Otimizador de rotas</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            Liste seus endereços abaixo (o primeiro é a base de saída e retorno).
            Usa OpenStreetMap + algoritmo da Colônia de Formigas pra achar a melhor ordem.
        </flux:text>
    </div>

    <flux:card>
        <form wire:submit="optimize" class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Endereços</flux:heading>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" icon="sparkles" wire:click.prevent="loadExample">
                        Exemplo
                    </flux:button>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click.prevent="clearAll">
                        Limpar
                    </flux:button>
                </div>
            </div>

            @error('addresses')
                <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
            @enderror

            <div class="space-y-2">
                @foreach ($addresses as $i => $addr)
                    <div class="flex items-center gap-2" wire:key="addr-{{ $i }}">
                        <flux:badge :color="$i === 0 ? 'emerald' : 'zinc'" size="lg" class="shrink-0 w-10 justify-center">
                            {{ $i === 0 ? '🏠' : $i }}
                        </flux:badge>

                        <div class="flex-1">
                            <flux:input
                                wire:model.live.debounce.300ms="addresses.{{ $i }}"
                                :placeholder="$i === 0 ? 'Endereço da base (saída e retorno)' : 'Parada ' . $i"
                            />
                        </div>

                        <flux:button.group>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="chevron-up"
                                wire:click.prevent="moveUp({{ $i }})"
                                :disabled="$i === 0"
                                title="Mover para cima"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="chevron-down"
                                wire:click.prevent="moveDown({{ $i }})"
                                :disabled="$i === count($addresses) - 1"
                                title="Mover para baixo"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click.prevent="removeAddress({{ $i }})"
                                :disabled="count($addresses) <= 3"
                                title="Remover"
                            />
                        </flux:button.group>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3">
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="plus"
                    wire:click.prevent="addAddress"
                >
                    Adicionar parada
                </flux:button>

                <flux:button type="submit" variant="primary" icon="map">
                    <span wire:loading.remove wire:target="optimize">Otimizar rota</span>
                    <span wire:loading wire:target="optimize">Otimizando…</span>
                </flux:button>
            </div>
        </form>
    </flux:card>

    {{-- Loading skeleton --}}
    <div wire:loading.flex wire:target="optimize" class="flex-col gap-3">
        <flux:card>
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon name="arrow-path" class="animate-spin size-5" />
                    <span>Geocodificando endereços e calculando rota… pode levar alguns segundos (Nominatim limita 1 req/s).</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-full w-1/3 animate-pulse bg-emerald-500"></div>
                </div>
            </div>
        </flux:card>
    </div>

    @if ($errorMessage)
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$errorMessage" />
    @endif

    @if ($result)
        @php
            $km = fn (float $m) => number_format($m / 1000, 2, ',', '.') . ' km';
            $gmaps = $result['google_maps_url'];
        @endphp

        <flux:card>
            <div class="space-y-3">
                <flux:text class="text-zinc-500">Distância total otimizada</flux:text>
                <flux:heading size="xl" class="text-emerald-600 dark:text-emerald-400">
                    {{ $km($result['cost_meters']) }}
                </flux:heading>

                @if ($result['savings_percent'] > 0.5)
                    <flux:text class="text-emerald-600 dark:text-emerald-400">
                        Economia de {{ number_format($result['savings_percent'], 1, ',', '.') }}%
                        vs. ordem digitada ({{ $km($result['original_cost_meters']) }}).
                    </flux:text>
                @endif

                <div class="flex flex-wrap gap-2 pt-2">
                    <flux:button
                        as="a"
                        href="{{ $gmaps }}"
                        target="_blank"
                        variant="primary"
                        icon="map-pin"
                    >
                        Abrir no Google Maps
                    </flux:button>

                    <flux:button
                        variant="ghost"
                        icon="clipboard"
                        x-data="{ url: @js($gmaps) }"
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

        {{-- Mapa interativo --}}
        <flux:card>
            <flux:heading size="lg" class="mb-3">Mapa da rota</flux:heading>
            <div
                wire:ignore
                x-data
                x-init="
                    const coords = @js($result['coordinates']);
                    const route = @js($result['route']);
                    const addresses = @js($result['addresses']);
                    const geometry = @js($result['route_geometry'] ?? null);

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

        <flux:card>
            <flux:heading size="lg" class="mb-4">Ordem de visita</flux:heading>
            <ol class="space-y-3">
                @foreach ($result['route'] as $pos => $idx)
                    @php
                        $isBase = ($pos === 0 || $pos === count($result['route']) - 1);
                        $label = $pos === 0
                            ? 'Base'
                            : ($pos === count($result['route']) - 1 ? 'Retorno' : "Parada {$pos}");
                    @endphp
                    <li class="flex items-start gap-3">
                        <flux:badge :color="$isBase ? 'emerald' : 'blue'" size="lg" class="shrink-0 w-10 justify-center">
                            {{ $isBase ? '🏠' : $pos }}
                        </flux:badge>
                        <div>
                            <div class="font-medium">{{ $result['addresses'][$idx] }}</div>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $label }} ·
                                {{ number_format($result['coordinates'][$idx]['lat'], 5) }},
                                {{ number_format($result['coordinates'][$idx]['lon'], 5) }}
                            </flux:text>
                        </div>
                    </li>
                @endforeach
            </ol>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-2">Convergência do algoritmo</flux:heading>
            <flux:text size="sm" class="text-zinc-500 mb-3">
                Melhor distância encontrada a cada iteração das formigas.
            </flux:text>
            <canvas id="aco-chart" width="900" height="240" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700"></canvas>
            <script>
                (function () {
                    const data = @json($result['history']);
                    const cv = document.getElementById('aco-chart');
                    if (!cv) return;
                    const ctx = cv.getContext('2d');
                    const W = cv.width, H = cv.height, p = 40;
                    const best = data.map(d => d.best);
                    const avg = data.map(d => d.average);
                    const all = best.concat(avg);
                    const mn = Math.min(...all) * 0.97;
                    const mx = Math.max(...all) * 1.03;
                    const X = i => p + (i / Math.max(1, data.length - 1)) * (W - 2 * p);
                    const Y = v => H - p - ((v - mn) / Math.max(0.001, mx - mn)) * (H - 2 * p);

                    ctx.clearRect(0, 0, W, H);
                    ctx.strokeStyle = '#52525b';
                    ctx.beginPath();
                    ctx.moveTo(p, p); ctx.lineTo(p, H - p); ctx.lineTo(W - p, H - p);
                    ctx.stroke();

                    ctx.strokeStyle = '#60a5fa'; ctx.lineWidth = 2; ctx.beginPath();
                    avg.forEach((v, i) => i ? ctx.lineTo(X(i), Y(v)) : ctx.moveTo(X(i), Y(v)));
                    ctx.stroke();

                    ctx.strokeStyle = '#10b981'; ctx.lineWidth = 2.5; ctx.beginPath();
                    best.forEach((v, i) => i ? ctx.lineTo(X(i), Y(v)) : ctx.moveTo(X(i), Y(v)));
                    ctx.stroke();
                })();
            </script>
        </flux:card>
    @endif
</div>
