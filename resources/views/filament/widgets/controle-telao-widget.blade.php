<x-filament-widgets::widget>
    {{-- Inclui o Vite com Echo --}}
    @vite(['resources/js/app.js'])
    
    <x-filament::section>
        <x-slot name="heading">
            Controle do Telão e Votação
        </x-slot>

        {{-- Container do Cronômetro do Operador com Alpine.js --}}
        <div 
            x-data="{
                segundosRestantes: @entangle('palavraSegundosRestantes'),
                status: @entangle('palavraStatus'),
                timestampInicio: @entangle('palavraTimestampInicio'),
                timerInterval: null,
                displayTime: '00:00',

                init() {
                    this.$watch('status', (newStatus, oldStatus) => {
                        if (newStatus === 'running') {
                            this.iniciarTimer();
                        } else {
                            this.pararTimer();
                        }
                    });

                    if (this.status === 'running') {
                        this.iniciarTimer();
                    } else {
                        this.atualizarDisplay(this.segundosRestantes);
                    }
                },

                iniciarTimer() {
                    this.pararTimer();
                    
                    // Sincroniza com o timestamp do servidor para evitar drift
                    const agora = Math.floor(Date.now() / 1000);
                    const inicio = this.timestampInicio || agora;
                    const segundosPassados = agora - inicio;
                    let tempoRealRestante = this.segundosRestantes - segundosPassados;

                    this.atualizarDisplay(tempoRealRestante);

                    this.timerInterval = setInterval(() => {
                        tempoRealRestante--;
                        this.segundosRestantes = tempoRealRestante; // Atualiza o wire model
                        this.atualizarDisplay(tempoRealRestante);

                        if (tempoRealRestante <= 0) {
                            this.pararTimer();
                            if (this.status === 'running') {
                                $wire.encerrarPalavra(); // Encerra automaticamente
                            }
                        }
                    }, 1000);
                },

                pararTimer() {
                    if (this.timerInterval) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                    }
                },

                atualizarDisplay(totalSegundos) {
                    if (totalSegundos < 0) totalSegundos = 0;
                    const minutos = Math.floor(totalSegundos / 60);
                    const segundos = totalSegundos % 60;
                    this.displayTime = `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
                },

                pausar() {
                    // Passa o tempo restante atual para o backend
                    $wire.pausarPalavra(this.segundosRestantes);
                }
            }"
            x-init="init()"
        >

            {{-- Botões de Layout --}}
            <div class="grid grid-cols-3 gap-3">
                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-normal')" 
                    icon="heroicon-o-video-camera" 
                    :color="$this->isCameraAtiva() ? 'success' : 'gray'" 
                    size="sm" 
                    class="w-full {{ $this->isCameraAtiva() ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
                    Câmera
                </x-filament::button>

                {{-- Dropdown Pauta Atual --}}
                <x-filament::dropdown placement="bottom-end">
                    <x-slot name="trigger">
                        <x-filament::button 
                            icon="heroicon-o-document-text" 
                            :color="$this->isPautaAtiva() ? 'success' : 'gray'" 
                            size="sm" 
                            class="w-full {{ $this->isPautaAtiva() ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
                            @if($this->isPautaAtiva() && $this->getPautaAtivaNumero())
                                {{ $this->getPautaAtivaNumero() }}
                            @else
                                Pauta Atual
                            @endif
                        </x-filament::button>
                    </x-slot>
                    <x-filament::dropdown.list>
                        @forelse ($this->getPautasSessaoAtivaOptions() as $pautaId => $pautaNumero)
                            <x-filament::dropdown.list.item 
                                href="#"
                                x-on:click.prevent="$wire.mudarLayoutTelao('layout-normal', { pauta_id: {{ $pautaId }} }); close()"
                                >
                                {{ $pautaNumero }}
                            </x-filament::dropdown.list.item>
                        @empty
                            <x-filament::dropdown.list.item disabled>
                                Nenhuma pauta na sessão.
                            </x-filament::dropdown.list.item>
                        @endforelse
                    </x-filament::dropdown.list>
                </x-filament::dropdown>

                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-voting')" 
                    icon="heroicon-o-chart-bar" 
                    :color="$this->isVotacaoAtiva() ? 'success' : 'gray'" 
                    size="sm" 
                    class="w-full {{ $this->isVotacaoAtiva() ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}" 
                    :disabled="!$this->pautaEmVotacao">
                    Votação
                </x-filament::button>

                {{-- Dropdown Palavra --}}
                <x-filament::dropdown placement="bottom-end">
                    <x-slot name="trigger">
                        <x-filament::button 
                            icon="heroicon-o-microphone" 
                            :color="$this->isPalavraAtiva() ? 'success' : 'gray'" 
                            size="sm" 
                            class="w-full {{ $this->isPalavraAtiva() ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
                            @if($this->isPalavraAtiva() && $this->getVereadorAtivoPalavra())
                                {{ $this->getVereadorAtivoPalavra() }}
                            @else
                                Palavra
                            @endif
                        </x-filament::button>
                    </x-slot>
                    <x-filament::dropdown.list>
                        @forelse ($this->getVereadoresOptions() as $vereadorId => $vereadorNome)
                            <x-filament::dropdown.list.item 
                                href="#"
                                x-on:click.prevent="$wire.selecionarVereadorParaPalavra({{ $vereadorId }}); close()"
                                >
                                {{ $vereadorNome }}
                            </x-filament::dropdown.list.item>
                        @empty
                            <x-filament::dropdown.list.item disabled>
                                Nenhum vereador.
                            </x-filament::dropdown.list.item>
                        @endforelse
                    </x-filament::dropdown.list>
                </x-filament::dropdown>

                <x-filament::button 
                    wire:click="mudarLayoutTelao('layout-normal')" 
                    icon="heroicon-o-tv" 
                    :color="$this->isCameraAtiva() ? 'success' : 'gray'" 
                    size="sm" 
                    class="w-full col-span-2 {{ $this->isCameraAtiva() ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
                    Tela Inicial
                </x-filament::button>
            </div>

            <hr class="dark:border-gray-700 my-4"/>

            {{-- Seção do Cronômetro Palavra --}}
            <div 
                class="space-y-3" 
                x-show="status !== 'stopped'" 
                x-transition
            >
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Controle da Palavra
                    </h4>

                    <div class="text-center space-y-3">
                        <p class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                            {{ $vereadorComPalavra?->nome_parlamentar ?? 'Nenhum vereador selecionado' }}
                        </p>
                        
                        <div class="text-4xl font-mono font-bold text-gray-800 dark:text-gray-200"
                             x-text="displayTime">
                            00:00
                        </div>

                        {{-- Botões de Tempo (só aparecem ao selecionar) --}}
                        <div x-show="status === 'selected'" class="grid grid-cols-3 gap-2">
                             <x-filament::button size="sm" color="gray" wire:click="concederPalavra(60)">1 min</x-filament::button>
                             <x-filament::button size="sm" color="gray" wire:click="concederPalavra(180)">3 min</x-filament::button>
                             <x-filament::button size="sm" color="gray" wire:click="concederPalavra(300)">5 min</x-filament::button>
                        </div>
                        
                        {{-- Botões de Controle (quando rodando ou pausado) --}}
                        <div x-show="status === 'running' || status === 'paused'" class="grid grid-cols-2 gap-2">
                            <template x-if="status === 'running'">
                                <x-filament::button size="sm" color="warning" icon="heroicon-o-pause" x-on:click="pausar()">
                                    Pausar
                                </x-filament::button>
                            </template>
                            <template x-if="status === 'paused'">
                                 <x-filament::button size="sm" color="success" icon="heroicon-o-play" wire:click="retomarPalavra()">
                                    Retomar
                                </x-filament::button>
                            </template>
                            
                            <x-filament::button size="sm" color="danger" icon="heroicon-o-stop" wire:click="encerrarPalavra()">
                                Encerrar
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Seção de Votação --}}
            <div>
                @if ($this->pautaEmVotacao)
                    <x-filament::button 
                        x-on:click="$dispatch('open-modal', { id: 'confirmar-encerrar-votacao' })"
                        icon="heroicon-o-stop" color="danger" size="sm" class="w-full">
                        Encerrar Votação
                    </x-filament::button>
                @else
                    <x-filament::button
                        x-on:click="$dispatch('open-modal', { id: 'abrir-votacao' })"
                        icon="heroicon-o-play" color="success" size="sm" class="w-full">
                        Abrir Votação
                    </x-filament::button>
                @endif
            
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

        </div> {{-- Fim do x-data --}}
    </x-filament::section>
    
    {{-- Modais v4 --}}
    
    {{-- Modal para Abrir Votação --}}
    <x-filament::modal id="abrir-votacao" width="lg">
        <x-slot name="heading">
            Abrir Nova Votação
        </x-slot>

        <x-slot name="description">
            Selecione a pauta que será colocada em votação.
        </x-slot>
        
        {{-- Conteúdo do Modal (Formulário) --}}
        <div class="mt-4">
            {{ $this->form }}
        </div>

        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'abrir-votacao' })">
                Cancelar
            </x-filament::button>
            
            <x-filament::button
                color="success"
                wire:click="confirmarAbrirVotacao">
                Confirmar Abertura
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Modal para Encerrar Votação --}}
    <x-filament::modal id="confirmar-encerrar-votacao" icon="heroicon-o-stop" icon-color="danger" width="lg">
        <x-slot name="heading">
            Encerrar Votação
        </x-slot>

        <x-slot name="description">
            @if ($this->pautaEmVotacao)
                Deseja encerrar a votação da pauta <strong>{{ $this->pautaEmVotacao->numero }}</strong>?
            @else
                Deseja encerrar a votação?
            @endif
        </x-slot>
        
        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'confirmar-encerrar-votacao' })">
                Cancelar
            </x-filament::button>
            
            <x-filament::button
                color="danger"
                wire:click="confirmarEncerrarVotacao">
                Encerrar
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

</x-filament-widgets::widget>