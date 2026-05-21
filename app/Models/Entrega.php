<?php

namespace App\Models;

use Database\Factories\EntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrega extends Model
{
    /** @use HasFactory<EntregaFactory> */
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_ATRIBUIDA = 'atribuida';

    public const STATUS_ENTREGUE = 'entregue';

    protected $table = 'entregas';

    protected $fillable = [
        'entregador_id',
        'endereco',
        'lat',
        'lon',
        'peso_kg',
        'volume_litros',
        'status',
        'ordem_na_rota',
        'entregue_em',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lon' => 'float',
            'peso_kg' => 'float',
            'volume_litros' => 'float',
            'ordem_na_rota' => 'integer',
            'entregue_em' => 'datetime',
        ];
    }

    public function entregador(): BelongsTo
    {
        return $this->belongsTo(Entregador::class);
    }
}
