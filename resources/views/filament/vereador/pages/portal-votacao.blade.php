@vite(['resources/js/app.js'])
<x-filament-panels::page>
    @if($votacaoAberta)
        <div style="display: grid; gap: 1rem;">
            <div>
                <strong>Pauta:</strong> {{ $pautaNumero ?? '—' }}
            </div>

            <div style="display: flex; gap: .5rem;">
                <x-filament::button color="success" wire:click="registrarVoto('sim')">SIM</x-filament::button>
                <x-filament::button color="danger" wire:click="registrarVoto('nao')">NÃO</x-filament::button>
                <x-filament::button color="warning" wire:click="registrarVoto('abst')">ABST</x-filament::button>
            </div>

            <div>
                <strong>Placar:</strong>
                SIM: {{ $placarSim }} | NÃO: {{ $placarNao }} | ABST: {{ $placarAbst }}
            </div>
        </div>
    @else
        <div>
            Aguardando abertura da votação...
        </div>
    @endif
</x-filament-panels::page>