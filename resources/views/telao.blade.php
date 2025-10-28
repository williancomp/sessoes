<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Plenário Ao Vivo - Câmara Municipal</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Estilos básicos para o telão (podem ser movidos para app.css) */
        body {
            background-color: #003366; /* Azul escuro típico */
            color: white;
            font-family: 'Figtree', sans-serif;
            overflow: hidden; /* Evitar barras de rolagem */
            margin: 0;
            padding: 20px; /* Um pouco de espaço nas bordas */
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header, .footer {
            flex-shrink: 0;
            text-align: center;
            padding: 10px 0;
        }
        .content {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ccc; /* Apenas para visualização */
            margin: 10px 0;
            position: relative; /* Para camadas futuras */
        }
        /* Camadas (inicialmente escondidas) */
        #layout-camera, #layout-pauta, #layout-votacao, #layout-palavra {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 51, 102, 0.9); /* Fundo um pouco diferente */
            display: none; /* Começam escondidas */
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        #layout-camera video {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body class="antialiased">

    <div class="header">
        <h1>CÂMARA MUNICIPAL DE [SUA CIDADE]</h1>
        {{-- Outras infos do header, como data/hora, podem vir aqui --}}
        <div id="placar-presenca">
            PRES: <span id="presenca-presentes">--</span> | AUSE: <span id="presenca-ausentes">--</span>
        </div>
    </div>

    <div class="content">
        {{-- Layout Padrão (ou mensagem inicial) --}}
        <div id="layout-inicial" style="display: flex; align-items: center; justify-content: center;">
            <h2>Sessão Plenária</h2>
        </div>

        {{-- Camada para Câmera --}}
        <div id="layout-camera">
            <video id="camera-feed" autoplay playsinline style="width:100%; height:100%; object-fit: cover;"></video>
        </div>

        {{-- Camada para Pauta Ativa --}}
        <div id="layout-pauta">
            <h2>Pauta em Discussão</h2>
            <p id="pauta-numero">Número: ...</p>
            <p id="pauta-descricao">Descrição: ...</p>
            <p id="pauta-autor">Autor: ...</p>
        </div>

        {{-- Camada para Votação --}}
        <div id="layout-votacao">
            <h2>EM VOTAÇÃO</h2>
            <p id="votacao-pauta-numero">Pauta: ...</p>
            <div id="votacao-placar">
                SIM: <span id="votacao-sim">0</span> | NÃO: <span id="votacao-nao">0</span> | ABST: <span id="votacao-abst">0</span>
            </div>
            <div id="votacao-lista-nominal" style="margin-top: 20px; text-align: left; max-height: 60vh; overflow-y: auto;">
                {{-- Lista de vereadores será populada via JS --}}
            </div>
        </div>

        {{-- Camada para "Com a Palavra" --}}
        <div id="layout-palavra">
            <h2>COM A PALAVRA</h2>
            <p id="palavra-vereador">Vereador(a): ...</p>
            <p>Tempo Restante: <span id="palavra-cronometro">--:--</span></p>
        </div>

    </div>

    <div class="footer">
        <p>Acompanhe também pelo Facebook e Youtube!</p>
    </div>

    <script>
        // Aguarde o Echo estar disponível
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.Echo) {
                console.error('Echo não está disponível! Verifique se app.js foi carregado.');
                return;
            }
            
            console.log('Echo initialized for Reverb');

            const layoutInicial = @json($layoutInicial);
            const dadosPautaInicial = @json($dadosPautaInicial);

            // Função auxiliar para mostrar apenas um layout e esconder os outros
            function showLayout(layoutId, data = null) {
                const layouts = ['layout-inicial', 'layout-camera', 'layout-pauta', 'layout-votacao', 'layout-palavra'];
                layouts.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.style.display = (id === layoutId) ? 'flex' : 'none';
                    }
                });
                console.log(`Showing layout: ${layoutId}`, data);

                // Atualizar dados da pauta se o layout for pauta ou votação
                if (layoutId === 'layout-pauta') {
                    document.getElementById('pauta-numero').textContent = `Número: ${data?.numero ?? '...'}`;
                    document.getElementById('pauta-descricao').innerHTML = `Descrição: ${data?.descricao ?? '...'}`; // Usar innerHTML para RichEditor
                    document.getElementById('pauta-autor').textContent = `Autor: ${data?.autor ?? '...'}`;
                } else if (layoutId === 'layout-votacao') {
                    // Atualiza info da pauta na tela de votação
                    document.getElementById('votacao-pauta-numero').textContent = `Pauta: ${data?.numero ?? '...'}`;
                    // NÃO zera o placar aqui, espera evento ContagemVotosAtualizada ou VotacaoAberta
                }

                // Lógica específica para ativar/desativar câmera (igual a antes)
                const videoElement = document.getElementById('camera-feed');
                // ... (código da câmera) ...
            }

            // Aguarde o Echo estar disponível
            document.addEventListener('DOMContentLoaded', function() {
                // *** INICIALIZAÇÃO ***
                showLayout(layoutInicial, dadosPautaInicial); // Define o layout inicial com dados, se houver

                if (!window.Echo) { /* ... (verificação do Echo) ... */ return; }
                console.log('Echo initialized for Reverb');

                window.Echo.channel('sessao-plenaria')
                    // ... (listener PresencaAtualizada) ...
                    .listen('LayoutTelaoAlterado', (e) => {
                        console.log('LayoutTelaoAlterado Event Received:', e);
                        showLayout(e.layout, e.dados); // Passa os dados recebidos
                    })
                    .listen('VotacaoAberta', (e) => {
                        console.log('VotacaoAberta Event Received:', e);
                        const pautaData = e.pauta;
                        showLayout('layout-votacao', pautaData); // Mostra layout e atualiza dados da pauta

                        // Zera placar e lista nominal ao ABRIR
                        document.getElementById('votacao-sim').textContent = '0';
                        document.getElementById('votacao-nao').textContent = '0';
                        document.getElementById('votacao-abst').textContent = '0';
                        const lista = document.getElementById('votacao-lista-nominal');
                        if (lista) lista.innerHTML = '';
                    })
                    // ... (listeners VotoRegistrado, ContagemVotosAtualizada, VotacaoEncerrada - sem alterações significativas) ...
                    .error((error) => { /* ... */ });

                console.log("Listening on channel 'sessao-plenaria'...");
            });

            // Conecta ao canal público da sessão plenária
            window.Echo.channel('sessao-plenaria')
                .listen('PresencaAtualizada', (e) => {
                    console.log('PresencaAtualizada Event Received:', e);
                    document.getElementById('presenca-presentes').textContent = e.contagemPresentes ?? '--';
                    document.getElementById('presenca-ausentes').textContent = e.contagemAusentes ?? '--';
                })
                .listen('LayoutTelaoAlterado', (e) => {
                    console.log('LayoutTelaoAlterado Event Received:', e);
                    showLayout(e.layout);

                    // Lógica específica para ativar/desativar câmera
                    const videoElement = document.getElementById('camera-feed');
                    if (e.layout === 'layout-camera') {
                        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                            navigator.mediaDevices.getUserMedia({ video: true })
                                .then(function(stream) {
                                    videoElement.srcObject = stream;
                                    videoElement.play();
                                })
                                .catch(function(err) {
                                    console.error("Erro ao acessar a câmera: ", err);
                                });
                        } else {
                            console.error("getUserMedia não suportado neste navegador.");
                        }
                    } else {
                        if (videoElement.srcObject) {
                            videoElement.srcObject.getTracks().forEach(track => track.stop());
                            videoElement.srcObject = null;
                        }
                    }
                })
                .listen('VotacaoAberta', (e) => {
                    console.log('VotacaoAberta Event Received:', e);
                    // Mostra o layout de votação
                    showLayout('layout-votacao');

                    // Atualiza a pauta exibida
                    const numero = e.pauta?.numero ?? '—';
                    document.getElementById('votacao-pauta-numero').textContent = `Pauta: ${numero}`;

                    // Zera placar
                    document.getElementById('votacao-sim').textContent = '0';
                    document.getElementById('votacao-nao').textContent = '0';
                    document.getElementById('votacao-abst').textContent = '0';

                    // Limpa lista nominal
                    const lista = document.getElementById('votacao-lista-nominal');
                    if (lista) lista.innerHTML = '';
                })
                .listen('VotoRegistrado', (e) => {
                    console.log('VotoRegistrado Event Received:', e);
                    const lista = document.getElementById('votacao-lista-nominal');
                    if (!lista) return;

                    const id = e.vereadorId ?? e.vereador_id;
                    const nome = e.nomeVereador ?? `Vereador ${id}`;
                    const voto = (e.voto ?? '').toLowerCase();

                    let item = document.getElementById(`vereador-${id}`);
                    if (!item) {
                        item = document.createElement('div');
                        item.id = `vereador-${id}`;
                        item.style.padding = '4px 8px';
                        item.style.borderBottom = '1px dashed rgba(255,255,255,0.2)';
                        lista.appendChild(item);
                    }
                    const cor = voto === 'sim' ? '#22c55e' : (voto === 'nao' ? '#ef4444' : '#f59e0b');
                    item.innerHTML = `<strong style="color:${cor}">${nome}</strong> — ${voto.toUpperCase()}`;
                })
                .listen('ContagemVotosAtualizada', (e) => {
                    console.log('ContagemVotosAtualizada Event Received:', e);
                    if (typeof e.sim !== 'undefined') document.getElementById('votacao-sim').textContent = e.sim;
                    if (typeof e.nao !== 'undefined') document.getElementById('votacao-nao').textContent = e.nao;
                    if (typeof e.abst !== 'undefined') document.getElementById('votacao-abst').textContent = e.abst;
                })
                .listen('VotacaoEncerrada', (e) => {
                    console.log('VotacaoEncerrada Event Received:', e);
                    // Retorna ao layout inicial ou outro conforme sua orquestração
                    showLayout('layout-inicial');
                })
                .error((error) => {
                    console.error('WebSocket Error:', error);
                });

            console.log("Listening on channel 'sessao-plenaria'...");
        });
    </script>
</body>
</html>