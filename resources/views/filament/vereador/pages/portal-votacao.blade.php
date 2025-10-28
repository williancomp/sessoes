@vite(['resources/js/app.js'])

<x-filament-panels::page>
    <div 
        class="space-y-4"
        {{-- 
            O wire:poll força o Livewire a verificar o estado no servidor a 
            cada 5 segundos. Isso garante que, mesmo se o Reverb falhar,
            a tela se corrigirá.
        --}}
        wire:poll.5s
    >
        
        {{-- Usamos @if do Blade, lendo as variáveis PHP --}}
        @if ($votacaoAberta)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold mb-2">Votação em Andamento</h2>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Pauta:</strong> {{ $pautaNumero ?? '—' }}
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <x-filament::button 
                        color="success" 
                        {{-- O wire:click chama a função PHP --}}
                        wire:click="registrarVoto('sim')"
                        size="xl" class="w-full">
                        <span class="text-lg font-bold">SIM</span>
                    </x-filament::button>
                    
                    <x-filament::button 
                        color="danger" 
                        wire:click="registrarVoto('nao')"
                        size="xl" class="w-full">
                        <span class="text-lg font-bold">NÃO</span>
                    </x-filament::button>
                    
                    <x-filament::button 
                        color="warning" 
                        wire:click="registrarVoto('abst')"
                        size="xl" class="w-full">
                        <span class="text-lg font-bold">ABSTENÇÃO</span>
                    </x-filament::button>
                </div>

                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-sm font-semibold mb-2">Placar em Tempo Real</h3>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            {{-- Lemos as variáveis PHP diretamente --}}
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $placarSim }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Sim</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $placarNao }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Não</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $placarAbst }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Abstenção</div>
                        </div>
                    </div>
                </div>
            </div>
        
        @else
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto mb-4 text-gray-400 dark:text-gray-600" style="width: 3rem; height: 3rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                    Aguardando abertura da votação
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    O painel atualizará automaticamente.
                </p>
            </div>
        @endif
    </div>
    
    {{-- GARANTA QUE NÃO HÁ NENHUM BLOCO @script AQUI --}}

</x-filament-panels::page>