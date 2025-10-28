<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vereador extends Model
{
    use HasFactory;

    protected $guarded = []; // Permite mass assignment
    protected $table = 'vereadores';

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}