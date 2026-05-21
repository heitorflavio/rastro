<?php

namespace Database\Seeders;

use App\Models\Entrega;
use App\Models\Entregador;
use App\Services\Exceptions\AddressNotFoundException;
use App\Services\Geocoder;
use Illuminate\Database\Seeder;

class RastroSeeder extends Seeder
{
    private const BASE = 'Rua Peroba, 75, Canelas, Montes Claros, MG';

    /**
     * Entregas atribuídas ao Heitor — total 113 kg / 163 L (sob 200 / 300).
     *
     * @var array<int, array{endereco:string, peso_kg:float, volume_litros:float}>
     */
    private const ENTREGAS_ATRIBUIDAS = [
        ['endereco' => 'Mercado Municipal Padre Tito, Montes Claros, MG', 'peso_kg' => 12, 'volume_litros' => 18],
        ['endereco' => 'Catedral Nossa Senhora Aparecida, Montes Claros, MG', 'peso_kg' => 8, 'volume_litros' => 12],
        ['endereco' => 'Shopping Ibituruna, Montes Claros, MG', 'peso_kg' => 25, 'volume_litros' => 35],
        ['endereco' => 'Estádio Independência, Montes Claros, MG', 'peso_kg' => 15, 'volume_litros' => 22],
        ['endereco' => 'Universidade Estadual de Montes Claros, MG', 'peso_kg' => 5, 'volume_litros' => 8],
        ['endereco' => 'Avenida Donato Quintino, 100, Ibituruna, Montes Claros, MG', 'peso_kg' => 20, 'volume_litros' => 28],
        ['endereco' => 'Avenida Coronel Prates, 500, Centro, Montes Claros, MG', 'peso_kg' => 10, 'volume_litros' => 15],
        ['endereco' => 'Avenida Doutor João Luiz de Almeida, 1000, Montes Claros, MG', 'peso_kg' => 18, 'volume_litros' => 25],
    ];

    /**
     * Entregas pendentes (sem entregador atribuído).
     *
     * @var array<int, array{endereco:string, peso_kg:float, volume_litros:float}>
     */
    private const ENTREGAS_PENDENTES = [
        ['endereco' => 'Aeroporto Mário Ribeiro, Montes Claros, MG', 'peso_kg' => 14, 'volume_litros' => 20],
        ['endereco' => 'Rodoviária de Montes Claros, MG', 'peso_kg' => 6, 'volume_litros' => 10],
    ];

    public function __construct(private readonly Geocoder $geocoder) {}

    public function run(): void
    {
        if (Entregador::where('nome', 'Heitor')->exists()) {
            $this->command?->warn('Heitor já existe — seeder pulado.');

            return;
        }

        $this->command?->info('Geocodificando endereço base do Heitor…');
        $base = $this->geocode(self::BASE);

        if (! $base) {
            $this->command?->error('Não consegui geocodificar a base ('.self::BASE.'). Abortei o seed.');

            return;
        }

        $heitor = Entregador::create([
            'nome' => 'Heitor',
            'endereco_base' => self::BASE,
            'lat_base' => $base['lat'],
            'lon_base' => $base['lon'],
            'peso_max_kg' => 200,
            'volume_max_litros' => 300,
        ]);

        $this->command?->info("Entregador criado: {$heitor->nome} (200 kg / 300 L)");

        foreach (self::ENTREGAS_ATRIBUIDAS as $i => $entrega) {
            $this->seedEntrega($entrega, atribuirA: $heitor, indice: $i + 1, total: count(self::ENTREGAS_ATRIBUIDAS));
        }

        foreach (self::ENTREGAS_PENDENTES as $i => $entrega) {
            $this->seedEntrega($entrega, atribuirA: null, indice: $i + 1, total: count(self::ENTREGAS_PENDENTES));
        }

        $this->command?->info('Pronto! Acesse /entregadores e clique no ícone de mapa para roteirizar.');
    }

    private function seedEntrega(array $dados, ?Entregador $atribuirA, int $indice, int $total): void
    {
        $tag = $atribuirA ? 'atribuída' : 'pendente';
        $this->command?->info("[{$tag} {$indice}/{$total}] Geocodificando: {$dados['endereco']}");

        $geo = $this->geocode($dados['endereco']);

        if (! $geo) {
            $this->command?->warn("  ↳ não encontrado, pulando.");

            return;
        }

        Entrega::create([
            'entregador_id' => $atribuirA?->id,
            'endereco' => $dados['endereco'],
            'lat' => $geo['lat'],
            'lon' => $geo['lon'],
            'peso_kg' => $dados['peso_kg'],
            'volume_litros' => $dados['volume_litros'],
            'status' => $atribuirA ? Entrega::STATUS_ATRIBUIDA : Entrega::STATUS_PENDENTE,
        ]);
    }

    private function geocode(string $endereco): ?array
    {
        try {
            $geo = $this->geocoder->geocode($endereco);
        } catch (AddressNotFoundException) {
            return null;
        }

        // Política do Nominatim: máx. 1 req/s
        if (! app()->runningUnitTests()) {
            usleep(1_100_000);
        }

        return $geo;
    }
}
