<x-filament-panels::page>
    @vite(['resources/js/app.js'])
    <div 
        class="space-y-4"
        
    >
        
        @if ($votacaoAberta)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Votação em Andamento
                    </h2>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Pauta:</strong> {{ $pautaNumero ?? '—' }}
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <x-filament::button 
                        color="success" 
                        wire:click="registrarVoto('sim')"
                        size="xl" 
                        class="w-full">
                        <span class="text-lg font-bold">SIM</span>
                    </x-filament::button>
                    
                    <x-filament::button 
                        color="danger" 
                        wire:click="registrarVoto('nao')"
                        size="xl" 
                        class="w-full">
                        <span class="text-lg font-bold">NÃO</span>
                    </x-filament::button>
                    
                    <x-filament::button 
                        color="warning" 
                        wire:click="registrarVoto('abst')"
                        size="xl" 
                        class="w-full">
                        <span class="text-lg font-bold">ABSTENÇÃO</span>
                    </x-filament::button>
                </div>

                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold mb-3 text-gray-900 dark:text-white">
                        Placar em Tempo Real
                    </h3>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ $placarSim }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Sim</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                                {{ $placarNao }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Não</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ $placarAbst }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Abstenção</div>
                        </div>
                    </div>
                </div>
            </div>
        
        @else
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
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

    <script>
        // Debug para verificar se Livewire está carregado
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Livewire carregado:', typeof window.Livewire !== 'undefined');
            console.log('Echo carregado:', typeof window.Echo !== 'undefined');
            console.log('DOM carregado');
            
            // Adicionar listener de clique para debug
            document.addEventListener('click', function(e) {
                if (e.target.closest('[wire\\:click]')) {
                    console.log('Clique em elemento Livewire detectado:', e.target);
                }
            });

            // Event listeners para atualização automática da votação
            if (window.Echo) {
                window.Echo.channel('sessao-plenaria')
                    .listen('.VotacaoAberta', (e) => {
                        console.log('VotacaoAberta recebido no portal do vereador:', e);
                        // Força refresh do componente Livewire
                        if (window.Livewire) {
                            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('$refresh');
                        }
                    })
                    .listen('.VotacaoEncerrada', (e) => {
                        console.log('VotacaoEncerrada recebido no portal do vereador:', e);
                        // Força refresh do componente Livewire
                        if (window.Livewire) {
                            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('$refresh');
                        }
                    })
                    .listen('.ContagemVotosAtualizada', (e) => {
                        console.log('ContagemVotosAtualizada recebido no portal do vereador:', e);
                        // Força refresh do componente Livewire para atualizar o placar
                        if (window.Livewire) {
                            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('$refresh');
                        }
                    });
            }

            // Sistema de Heartbeat para detectar inatividade
            let heartbeatInterval;
            
            function enviarHeartbeat() {
                fetch('/vereador/heartbeat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Heartbeat enviado:', data);
                })
                .catch(error => {
                    console.error('Erro no heartbeat:', error);
                });
            }

            // Enviar heartbeat a cada 30 segundos
            heartbeatInterval = setInterval(enviarHeartbeat, 30000);
            
            // Enviar heartbeat imediatamente
            enviarHeartbeat();

            // Limpar interval quando a página for fechada
            window.addEventListener('beforeunload', function() {
                if (heartbeatInterval) {
                    clearInterval(heartbeatInterval);
                }
            });

            // Detectar quando a aba perde o foco (opcional - para debug)
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    console.log('Aba ficou inativa');
                } else {
                    console.log('Aba ficou ativa - enviando heartbeat');
                    enviarHeartbeat();
                }
            });
        });
    </script>
</x-filament-panels::page>