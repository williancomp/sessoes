<?php

namespace App\Filament\Resources\Vereadors\Pages;

use App\Filament\Resources\Vereadors\VereadorResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateVereador extends CreateRecord
{
    protected static string $resource = VereadorResource::class;

    protected $userData = []; // Propriedade temporária

    /**
     * Intercepta os dados do formulário ANTES de criar o Vereador.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Guarda os dados do usuário para usar depois
        $this->userData = [
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'name' => $data['nome_parlamentar'], // Usa o nome parlamentar como nome de usuário
        ];

        // Remove os campos do usuário do array principal,
        // pois eles não existem na tabela 'vereadores'
        unset($data['email'], $data['password']);

        return $data; // Retorna apenas os dados do Vereador
    }

    /**
     * É chamado DEPOIS que o Vereador foi criado.
     * Agora vamos criar o User e associar.
     */
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Cria o registro principal (Vereador)
        $vereador = static::getModel()::create($data);

        // 2. Cria o Usuário com os dados que guardamos
        $user = User::create($this->userData);

        // 3. Atribui o papel "Vereador"
        $user->assignRole('Vereador');

        // 4. Associa o user_id ao Vereador
        $vereador->user_id = $user->id;
        $vereador->save();

        return $vereador;
    }
}
