<?php

namespace App\Models;

use Database\Factories\EntregadorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entregador extends Model
{
    /** @use HasFactory<EntregadorFactory> */
    use HasFactory;

    protected $table = 'entregadores';

    protected $fillable = [
        'nome',
        'endereco_base',
        'lat_base',
        'lon_base',
        'peso_max_kg',
        'volume_max_litros',
    ];

    protected function casts(): array
    {
        return [
            'lat_base' => 'float',
            'lon_base' => 'float',
            'peso_max_kg' => 'float',
            'volume_max_litros' => 'float',
        ];
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }

    public function entregasAtribuidas(): HasMany
    {
        return $this->entregas()->where('status', 'atribuida');
    }

    public function pesoAtribuido(): float
    {
        return (float) $this->entregasAtribuidas()->sum('peso_kg');
    }

    public function volumeAtribuido(): float
    {
        return (float) $this->entregasAtribuidas()->sum('volume_litros');
    }

    public function suportaCarga(float $peso, float $volume): bool
    {
        return ($this->pesoAtribuido() + $peso) <= $this->peso_max_kg
            && ($this->volumeAtribuido() + $volume) <= $this->volume_max_litros;
    }
}
