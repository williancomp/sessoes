<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Legislatura extends Model
{
    protected $fillable = ["ano_inicio", "ano_fim", "ativa"];
}
