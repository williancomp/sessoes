<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sessao extends Model
{
    protected $table = "sessoes";

    protected $guarded = [];

    /**
     * Define os casts dos atributos.
     * Adicione esta propriedade:
     */
    protected $casts = [
        'data' => 'date', // Trata a coluna 'data' como um objeto Carbon (date ou datetime)
    ];

    /**
     * Get the pautas for the sessao.
     */
    public function pautas(): HasMany 
    {
        // Assumes 'sessao_id' foreign key on the Pauta model
        return $this->hasMany(Pauta::class);
    }

    /**
     * Get the Legislatura that owns the Sessao.
     */
    public function legislatura(): BelongsTo 
    {
        return $this->belongsTo(Legislatura::class);
    }

    public function presencas(): HasMany // <--- ADICIONE ESTE MÉTODO
    {
        // Assumes 'sessao_id' foreign key on the Presenca model
        return $this->hasMany(Presenca::class);
    }
}
