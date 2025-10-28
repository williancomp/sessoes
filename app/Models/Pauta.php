<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pauta extends Model
{
    protected $guarded = [];

    /**
     * Get the Sessao that owns the Pauta.
     */
    public function sessao(): BelongsTo 
    {
        return $this->belongsTo(Sessao::class);
    }

    /**
     * Get the TipoPauta that owns the Pauta.
     */
    public function tipoPauta(): BelongsTo 
    {
        return $this->belongsTo(TipoPauta::class);
    }
}
