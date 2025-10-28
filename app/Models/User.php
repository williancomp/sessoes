<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // MODIFIQUE ESTE MÉTODO
    public function canAccessPanel(Panel $panel): bool
    {
        // O Super Admin (ou qualquer Admin) pode acessar todos os painéis.
        if ($this->hasRole('super_admin') || $this->hasRole('Admin')) {
            return true;
        }

        // Verifica o ID do painel sendo acessado
        return match ($panel->getId()) {
            'admin' => false, // Apenas Admins podem acessar /admin
            'presidente' => $this->hasRole('Presidente'), // Apenas Presidentes podem acessar /presidencia
            'vereador' => $this->hasRole('Vereador'), // Apenas Vereadores podem acessar /portal
            default => false, // Bloqueia qualquer outro painel por segurança
        };
    }
}
