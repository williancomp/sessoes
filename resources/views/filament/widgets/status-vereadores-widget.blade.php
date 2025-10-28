<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Status dos Vereadores (Sessão: {{ $sessaoAtivaId ?? 'Nenhuma' }})
        </x-slot>

        @if ($sessaoAtivaId)
            <div 
                class="max-h-96 overflow-y-auto space-y-2 pr-2" 
                {{-- O wire:poll atualiza via Livewire como fallback --}}
                wire:poll.10s="carregarDados" 
            >
                @forelse ($vereadores as $vereador)
                    <div class="flex items-center justify-between p-2 rounded border dark:border-gray-700">
                        <div class="flex items-center space-x-2">
                            {{-- Status Presença --}}
                            <span @class([
                                'h-3 w-3 rounded-full',
                                'bg-green-500' => $presenca[$vereador->id] ?? false,
                                'bg-gray-400 dark:bg-gray-600' => !($presenca[$vereador->id] ?? false),
                            ])></span>
                            <span>{{ $vereador->nome_parlamentar }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $vereador->partido?->sigla ?? 'S/P' }})</span>
                        </div>

                        {{-- Status Voto (se aplicável) --}}
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
                                    'sim' => 'text-green-600 dark:text-green-400',
                                    'nao' => 'text-red-600 dark:text-red-400',
                                    'abst' => 'text-yellow-600 dark:text-yellow-400',
                                    default => 'text-gray-400 dark:text-gray-500'
                                };
                            @endphp
                            <span class="font-mono text-sm font-semibold {{ $votoCor }}">
                               VOTO: {{ $votoTexto }}
                            </span>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400">Nenhum vereador cadastrado.</p>
                @endforelse
            </div>
         @else
             <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma sessão ativa para monitorar.</p>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>