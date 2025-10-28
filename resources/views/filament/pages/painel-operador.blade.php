<x-filament-panels::page>
    {{--
        Este @if controla qual conjunto de widgets é renderizado
        baseado na propriedade $sessaoAtiva da Page class.
    --}}
    @if ($this->sessaoAtiva)
        {{-- Renderiza os widgets definidos para sessão ativa com layout de colunas --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
    @else
        {{-- Renderiza o widget de seleção de sessão em largura total --}}
        <div class="col-span-full"> {{-- Garante que o widget use toda a largura disponível --}}
             <x-filament-widgets::widgets :widgets="$this->getWidgets()" :columns="1" />
        </div>
    @endif
</x-filament-panels::page>