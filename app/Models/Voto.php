<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voto extends Model
{
    use HasFactory;

    protected $fillable = [
        'pauta_id',
        'vereador_id',
        'voto',
    ];

    public function pauta(): BelongsTo
    {
        return $this->belongsTo(Pauta::class);
    }

    public function vereador(): BelongsTo
    {
        return $this->belongsTo(Vereador::class);
    }
}
