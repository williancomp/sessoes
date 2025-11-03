<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Status dos Vereadores (Sessão: {{ $sessaoAtivaId ?? 'Nenhuma' }})
            @if($pautaEmVotacaoId)
                <span class="text-xs text-warning-600 ml-2">[Votação Ativa]</span>
            @endif
        </x-slot>

        @if ($sessaoAtivaId)
            {{-- Adiciona wire:poll para garantir atualização --}}
            <div 
                class="max-h-96 overflow-y-auto space-y-2 pr-2" 
                wire:poll.2s="poll"
                wire:key="vereadores-list-{{ $updateCounter }}"
            >
                @forelse ($vereadores as $vereador)
                    <div 
                        class="flex items-center justify-between p-2 rounded border dark:border-gray-700"
                        wire:key="vereador-{{ $vereador->id }}-{{ $updateCounter }}"
                    >
                        <div class="flex items-center space-x-2">
                            {{-- Status Presença --}}
                            <span 
                                wire:key="presenca-icon-{{ $vereador->id }}-{{ $updateCounter }}-{{ ($presenca[$vereador->id] ?? false) ? 'presente' : 'ausente' }}"
                                @class([
                                    'h-3 w-3 rounded-full transition-colors duration-300',
                                    'bg-green-500 shadow-green-300 shadow-sm' => $presenca[$vereador->id] ?? false,
                                    'bg-gray-400 dark:bg-gray-600' => !($presenca[$vereador->id] ?? false),
                                ])
                                title="{{ ($presenca[$vereador->id] ?? false) ? 'Presente' : 'Ausente' }}"
                            ></span>
                            <span>{{ $vereador->nome_parlamentar }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $vereador->partido?->sigla ?? 'S/P' }})</span>
                        </div>

                        {{-- Status Voto com key único para forçar re-render --}}
                        @if ($pautaEmVotacaoId)
                            @php
                                $voto = $votos[$vereador->id] ?? null;
                                $votoTexto = match($voto) {
                                    'sim' => 'SIM',
                                    'nao' => 'NÃO',
                                    'abst' => 'ABST',
                                    default => '-'
                                };
                                $votoCor = match($voto) {
                                    'sim' => 'text-green-600 dark:text-green-400 font-bold',
                                    'nao' => 'text-red-600 dark:text-red-400 font-bold',
                                    'abst' => 'text-yellow-600 dark:text-yellow-400 font-bold',
                                    default => 'text-gray-400 dark:text-gray-500'
                                };
                            @endphp
                            <span 
                                wire:key="voto-{{ $vereador->id }}-{{ $voto }}-{{ $updateCounter }}"
                                class="font-mono text-sm {{ $votoCor }}"
                            >
                               VOTO: {{ $votoTexto }}
                            </span>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400">Nenhum vereador cadastrado.</p>
                @endforelse
            </div>
            
            {{-- Debug info (remover em produção) --}}
            <div class="mt-4 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
                <p>Debug Info:</p>
                <p>Update Counter: {{ $updateCounter }}</p>
                <p>Total Votos: {{ count($votos) }}</p>
                <p>Pauta ID: {{ $pautaEmVotacaoId ?? 'null' }}</p>
                <p>Votos: {{ json_encode($votos) }}</p>
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma sessão ativa para monitorar.</p>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>