<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Controle da Sessão Plenária
        </x-slot>

        @if ($this->sessaoEstaAtiva)
            <div class="flex items-center justify-center space-x-4">
                <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                    Sessão em Andamento (ID: {{ $this->sessaoAtivaId }})
                </p>
                <x-filament::button
                    wire:click="encerrarSessao"
                    color="danger"
                    icon="heroicon-o-stop-circle">
                    Encerrar Sessão Atual
                </x-filament::button>
            </div>
        @else
            <div class="space-y-4">
                <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma sessão em andamento.</p>
                <div class="flex items-end space-x-4">
                    <div class="flex-grow">
                        {{ $this->form }}
                    </div>
                    <x-filament::button
                        wire:click="iniciarSessao"
                        icon="heroicon-o-play-circle"
                        :disabled="!$sessao_id_para_iniciar">
                        Iniciar Sessão Selecionada
                    </x-filament::button>
                </div>
             </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>