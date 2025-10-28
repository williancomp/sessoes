@php
    // Necessário para as ações com modais funcionarem corretamente
    use Filament\Forms\Components\Actions\Action;
@endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Controle do Telão e Votação
        </x-slot>

        <div class="space-y-4">
            {{-- Botões de Layout --}}
            <div class="grid grid-cols-2 gap-2">
                <x-filament::button wire:click="mudarLayoutTelao('layout-camera')" icon="heroicon-o-video-camera" color="gray" class="w-full">
                    Câmera
                </x-filament::button>

                {{-- Botão Pauta com Dropdown --}}
                <x-filament::dropdown placement="bottom-end">
                    <x-slot name="trigger">
                        <x-filament::button icon="heroicon-o-document-text" color="gray" class="w-full">
                            Pauta Atual
                        </x-filament::button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        @forelse ($this->getPautasSessaoAtivaOptions() as $pautaId => $pautaNumero)
                            <x-filament::dropdown.list.item wire:click="mudarLayoutTelao('layout-pauta', {{ $pautaId }})">
                                {{ $pautaNumero }}
                            </x-filament::dropdown.list.item>
                        @empty
                            <x-filament::dropdown.list.item disabled>
                                Nenhuma pauta na sessão ativa.
                            </x-filament::dropdown.list.item>
                        @endforelse
                    </x-filament::dropdown.list>
                </x-filament::dropdown>

                <x-filament::button wire:click="mudarLayoutTelao('layout-votacao')" icon="heroicon-o-chart-bar" color="gray" class="w-full" :disabled="!$this->pautaEmVotacao">
                    Votação
                </x-filament::button>
                <x-filament::button wire:click="mudarLayoutTelao('layout-palavra')" icon="heroicon-o-microphone" color="gray" class="w-full">
                    Palavra
                </x-filament::button>
                 <x-filament::button wire:click="mudarLayoutTelao('layout-inicial')" icon="heroicon-o-tv" color="gray" class="w-full col-span-2">
                    Tela Inicial
                </x-filament::button>
            </div>

            <hr class="dark:border-gray-700"/>

            {{-- Botões de Votação --}}
            <div class="grid grid-cols-2 gap-2">
                 {{-- Botão Abrir Votação (chama a Action que abre o modal) --}}
                 {{ $this->abrirVotacaoAction }}

                 {{-- Botão Encerrar Votação (chama a Action que abre o modal) --}}
                 {{ $this->encerrarVotacaoAction }}
            </div>

            {{-- Exibe qual pauta está em votação --}}
             @if ($this->pautaEmVotacao)
                 <div class="mt-2 text-sm text-center text-yellow-600 dark:text-yellow-400">
                     Em votação: <strong>{{ $this->pautaEmVotacao->numero }}</strong>
                 </div>
             @else
                <div class="mt-2 text-sm text-center text-gray-500 dark:text-gray-400">
                     Nenhuma pauta em votação.
                 </div>
             @endif

        </div>
    </x-filament::section>

    {{-- Necessário para renderizar os modais das actions --}}
    <x-filament-actions::modals />
</x-filament-widgets::widget>