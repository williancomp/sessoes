<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Controle do Telão e Votação
        </x-slot>

        <div class="space-y-4">
            {{-- Botões de Layout --}}
            <div class="grid grid-cols-2 gap-3">
                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-camera')" 
                    icon="heroicon-o-video-camera" 
                    color="gray"
                    size="sm"
                    class="w-full">
                    Câmera
                </x-filament::button>

                {{-- Botão Pauta com Dropdown --}}
                <x-filament::dropdown placement="bottom-end">
                    <x-slot name="trigger">
                        <x-filament::button 
                            icon="heroicon-o-document-text" 
                            color="gray"
                            size="sm"
                            class="w-full">
                            Pauta Atual
                        </x-filament::button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        @forelse ($this->getPautasSessaoAtivaOptions() as $pautaId => $pautaNumero)
                            <x-filament::dropdown.list.item 
                                wire:click="mudarLayoutTelao('layout-pauta', {{ $pautaId }})">
                                {{ $pautaNumero }}
                            </x-filament::dropdown.list.item>
                        @empty
                            <x-filament::dropdown.list.item disabled>
                                Nenhuma pauta na sessão ativa.
                            </x-filament::dropdown.list.item>
                        @endforelse
                    </x-filament::dropdown.list>
                </x-filament::dropdown>

                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-votacao')" 
                    icon="heroicon-o-chart-bar" 
                    color="gray"
                    size="sm"
                    class="w-full" 
                    :disabled="!$this->pautaEmVotacao">
                    Votação
                </x-filament::button>

                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-palavra')" 
                    icon="heroicon-o-microphone" 
                    color="gray"
                    size="sm"
                    class="w-full">
                    Palavra
                </x-filament::button>

                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-inicial')" 
                    icon="heroicon-o-tv" 
                    color="gray"
                    size="sm"
                    class="w-full col-span-2">
                    Tela Inicial
                </x-filament::button>
            </div>

            <hr class="dark:border-gray-700"/>

            {{-- Seção de Abrir Votação --}}
            @if (!$this->pautaEmVotacao && $mostrarFormAbrir)
                <div class="p-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20">
                    <h3 class="text-sm font-semibold mb-3 text-green-900 dark:text-green-100">
                        Selecione a pauta para votação:
                    </h3>
                    
                    {{ $this->form }}
                    
                    <div class="flex gap-3 mt-4">
                        <x-filament::button
                            color="gray"
                            size="sm"
                            wire:click="cancelarAbrirVotacao"
                            class="flex-1">
                            Cancelar
                        </x-filament::button>
                        
                        <x-filament::button
                            color="success"
                            size="sm"
                            wire:click="confirmarAbrirVotacao"
                            class="flex-1">
                            Confirmar
                        </x-filament::button>
                    </div>
                </div>
            @elseif (!$this->pautaEmVotacao)
                {{-- Botão para mostrar formulário --}}
                <x-filament::button
                    wire:click="mostrarFormularioAbrir"
                    icon="heroicon-o-play"
                    color="success"
                    size="sm"
                    class="w-full">
                    Abrir Votação
                </x-filament::button>
            @endif

            {{-- Seção de Encerrar Votação --}}
            @if ($this->pautaEmVotacao && $mostrarConfirmacaoEncerrar)
                <div class="p-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <h3 class="text-sm font-semibold mb-2 text-red-900 dark:text-red-100">
                        Confirmar encerramento
                    </h3>
                    <p class="text-sm text-red-800 dark:text-red-200 mb-4">
                        Deseja encerrar a votação da pauta <strong>{{ $this->pautaEmVotacao->numero }}</strong>?
                    </p>
                    
                    <div class="flex gap-3">
                        <x-filament::button
                            color="gray"
                            size="sm"
                            wire:click="cancelarEncerrarVotacao"
                            class="flex-1">
                            Cancelar
                        </x-filament::button>
                        
                        <x-filament::button
                            color="danger"
                            size="sm"
                            wire:click="confirmarEncerrarVotacao"
                            class="flex-1">
                            Encerrar
                        </x-filament::button>
                    </div>
                </div>
            @elseif ($this->pautaEmVotacao)
                {{-- Botão para mostrar confirmação --}}
                <x-filament::button
                    wire:click="mostrarConfirmacaoEncerrar"
                    icon="heroicon-o-stop"
                    color="danger"
                    size="sm"
                    class="w-full">
                    Encerrar Votação
                </x-filament::button>
            @endif

            {{-- Exibe qual pauta está em votação --}}
            @if ($this->pautaEmVotacao)
                <div class="mt-2 p-2 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                    <p class="text-sm text-center text-yellow-800 dark:text-yellow-200">
                        <strong>Em votação:</strong> {{ $this->pautaEmVotacao->numero }}
                    </p>
                </div>
            @else
                <div class="mt-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800">
                    <p class="text-sm text-center text-gray-600 dark:text-gray-400">
                        Nenhuma pauta em votação.
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>