<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presenca extends Model
{
    use HasFactory;

    protected $fillable = [ // Permite mass assignment seguro
        'sessao_id',
        'vereador_id',
        'presente',
        'horario_login',
    ];

    protected $casts = [ // Converte tipos de dados automaticamente
        'presente' => 'boolean',
        'horario_login' => 'datetime',
    ];

    public function sessao(): BelongsTo
    {
        return $this->belongsTo(Sessao::class);
    }

    public function vereador(): BelongsTo
    {
        return $this->belongsTo(Vereador::class);
    }
}
