<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="estado-global" content="{{ json_encode($estadoGlobal ?? []) }}">

    <title>Plenário Ao Vivo - Câmara Municipal</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background-color: #003366;
            color: white;
            font-family: 'Figtree', sans-serif;
            overflow: hidden;
            margin: 0;
            padding: 20px;
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
            border: 2px solid #ccc;
            margin: 10px 0;
            position: relative;
        }
        #layout-camera, #layout-pauta, #layout-votacao, #layout-palavra {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 51, 102, 0.9);
            display: none;
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
        #layout-palavra h2 { font-size: 2.5rem; font-weight: bold; }
        #palavra-vereador { font-size: 2rem; color: #f59e0b; }
        #palavra-cronometro { font-size: 4rem; font-family: monospace; font-weight: bold; }
    </style>
</head>
<body class="antialiased">

    <div class="header">
        <h1>CÂMARA MUNICIPAL DE [SUA CIDADE]</h1>
        <div id="placar-presenca">
            PRES: <span id="presenca-presentes">--</span> | AUSE: <span id="presenca-ausentes">--</span>
        </div>
    </div>

    <div class="content">
        <div id="layout-inicial" style="display: flex; align-items: center; justify-content: center;">
            <h2>Sessão Plenária</h2>
        </div>

        <div id="layout-camera">
            <video id="camera-feed" autoplay playsinline style="width:100%; height:100%; object-fit: cover;"></video>
        </div>

        <div id="layout-pauta">
            <h2>Pauta em Discussão</h2>
            <p id="pauta-numero">Número: ...</p>
            <p id="pauta-descricao">Descrição: ...</p>
            <p id="pauta-autor">Autor: ...</p>
        </div>

        <div id="layout-votacao">
            <h2>EM VOTAÇÃO</h2>
            <p id="votacao-pauta-numero">Pauta: ...</p>
            <div id="votacao-placar">
                SIM: <span id="votacao-sim">0</span> | NÃO: <span id="votacao-nao">0</span> | ABST: <span id="votacao-abst">0</span>
            </div>
            <div id="votacao-lista-nominal" style="margin-top: 20px; text-align: left; max-height: 60vh; overflow-y: auto;">
            </div>
        </div>

        <div id="layout-palavra">
            <div style="text-align: center;">
                <h2>COM A PALAVRA</h2>
                <p id="palavra-vereador">Vereador(a): ...</p>
                <p>Tempo Restante: <span id="palavra-cronometro">--:--</span></p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Acompanhe também pelo Facebook e Youtube!</p>
    </div>

    <script>
        let layoutId = 'layout-inicial';
        let pautaData = null;
        let estadoGlobal = null;


        // --- NOVO: Variáveis do Cronômetro ---
        let telaoTimerInterval = null;
        let telaoSegundosRestantes = 0;
        let telaoTimestampInicio = null;
        let telaoTimerStatus = 'stopped';

        // Sincroniza com estado global do servidor
        function sincronizarEstadoGlobal() {
            const headers = document.querySelector('meta[name="estado-global"]');
            if (headers) {
                try {
                    estadoGlobal = JSON.parse(headers.getAttribute('content'));
                    aplicarEstadoGlobal();
                } catch (e) {
                    console.warn('Erro ao parsear estado global:', e);
                }
            }
        }

        // Função para mostrar apenas o layout especificado
        function mostrarLayout(id, dados = null) {
            const layouts = ['layout-inicial', 'layout-camera', 'layout-pauta', 'layout-votacao', 'layout-palavra'];
            layouts.forEach(layoutId => {
                const element = document.getElementById(layoutId);
                if (element) {
                    element.style.display = (layoutId === id) ? 'flex' : 'none';
                }
            });
            console.log(`Showing layout: ${id}`, dados);

            if (id === 'layout-pauta') {
                document.getElementById('pauta-numero').textContent = `Número: ${dados?.numero ?? '...'}`;
                document.getElementById('pauta-descricao').innerHTML = `Descrição: ${dados?.descricao ?? '...'}`;
                document.getElementById('pauta-autor').textContent = `Autor: ${dados?.autor ?? '...'}`;
            } else if (id === 'layout-votacao') {
                document.getElementById('votacao-pauta-numero').textContent = `Pauta: ${dados?.numero ?? '...'}`;
            } else if (id === 'layout-palavra') {
                // ATUALIZADO: 'vereador' pode estar dentro de 'dados' ou 'dados.vereador'
                const vereador = dados?.vereador || dados;
                document.getElementById('palavra-vereador').textContent = `Vereador(a): ${vereador?.nome_parlamentar ?? '...'}`;
            }

            const videoElement = document.getElementById('camera-feed');
            if (id === 'layout-camera') {
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
        }

        // Atualiza dados de votação ativa
        function atualizarDadosVotacao(dadosVotacao) {
            if (dadosVotacao && dadosVotacao.pauta) {
                document.getElementById('votacao-pauta-numero').textContent = `Pauta: ${dadosVotacao.pauta.numero ?? '...'}`;
            }
        }

        // Atualiza contagem de votos
        function atualizarContagemVotos(contagem) {
            if (typeof contagem.sim !== 'undefined') document.getElementById('votacao-sim').textContent = contagem.sim;
            if (typeof contagem.nao !== 'undefined') document.getElementById('votacao-nao').textContent = contagem.nao;
            if (typeof contagem.abst !== 'undefined') document.getElementById('votacao-abst').textContent = contagem.abst;
        }


        function formatarTempo(totalSegundos) {
            if (totalSegundos < 0) totalSegundos = 0;
            const minutos = Math.floor(totalSegundos / 60);
            const segundos = totalSegundos % 60;
            return `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
        }

        function atualizarDisplayCronometro(segundos) {
            document.getElementById('palavra-cronometro').textContent = formatarTempo(segundos);
        }

        function pararCronometroTelao() {
            if (telaoTimerInterval) {
                clearInterval(telaoTimerInterval);
                telaoTimerInterval = null;
            }
            telaoTimerStatus = 'paused';
        }

        function iniciarCronometroTelao(segundosIniciais, timestampInicio) {
            pararCronometroTelao(); // Limpa qualquer timer anterior
            telaoTimerStatus = 'running';
            
            const agora = Math.floor(Date.now() / 1000);
            const inicio = timestampInicio || agora;
            const segundosPassados = agora - inicio;
            let segundosRestantes = segundosIniciais - segundosPassados;

            atualizarDisplayCronometro(segundosRestantes);

            telaoTimerInterval = setInterval(() => {
                segundosRestantes--;
                telaoSegundosRestantes = segundosRestantes;
                atualizarDisplayCronometro(segundosRestantes);

                if (segundosRestantes <= 0) {
                    pararCronometroTelao();
                    telaoTimerStatus = 'stopped';
                }
            }, 1000);
        }



        // Aplica o estado global carregado
        function aplicarEstadoGlobal() {
            if (!estadoGlobal) return;

            // Aplica layout do telão
            if (estadoGlobal.telao_layout) {
                layoutId = estadoGlobal.telao_layout.layout || 'layout-inicial';
                pautaData = estadoGlobal.telao_layout.dados;
                mostrarLayout(layoutId, pautaData);
            }

            // Aplica dados de votação ativa
            if (estadoGlobal.votacao_ativa) {
                atualizarDadosVotacao(estadoGlobal.votacao_ativa);
            }

            // Aplica contagem de votos
            if (estadoGlobal.contagem_votos) {
                atualizarContagemVotos(estadoGlobal.contagem_votos);
            }

            if (estadoGlobal.palavra_ativa) {
                const palavra = estadoGlobal.palavra_ativa;
                telaoSegundosRestantes = palavra.segundos_restantes;
                telaoTimestampInicio = palavra.timestamp_inicio;
                telaoTimerStatus = palavra.status;
                
                // Atualiza o nome do vereador no layout-palavra
                if (palavra.vereador) {
                    document.getElementById('palavra-vereador').textContent = `Vereador(a): ${palavra.vereador.nome_parlamentar}`;
                }

                if (palavra.status === 'running') {
                    iniciarCronometroTelao(palavra.segundos_restantes, palavra.timestamp_inicio);
                } else {
                    // Pausado ou parado
                    pararCronometroTelao();
                    atualizarDisplayCronometro(palavra.segundos_restantes);
                }
            } else {
                // Se não há palavra ativa, limpa
                pararCronometroTelao();
                atualizarDisplayCronometro(0);
                if (layoutId === 'layout-palavra') {
                     // Se o layout é palavra mas não há estado, reseta o nome
                    document.getElementById('palavra-vereador').textContent = 'Vereador(a): ...';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (!window.Echo) {
                console.error('Echo não está disponível! Verifique se app.js foi carregado.');
                return;
            }
            
            console.log('Echo initialized for Reverb');

            const layoutInicial = @json($layoutInicial);
            const dadosPautaInicial = @json($dadosPautaInicial);

            
            // INICIALIZAÇÃO
            sincronizarEstadoGlobal();
            mostrarLayout(layoutInicial, dadosPautaInicial);

            // Função para sincronizar com estado global via polling (fallback)
            function sincronizarPeriodicamente() {
                
            }

            // LIVEWIRE 3: Listeners otimizados com estado global
            window.Echo.channel('sessao-plenaria')
                .listen('.PresencaAtualizada', (e) => {
                    console.log('PresencaAtualizada Event Received:', e);
                    // Atualiza estado global local
                    if (window.estadoGlobal) {
                        window.estadoGlobal.presenca = {
                            presentes: e.contagemPresentes,
                            ausentes: e.contagemAusentes
                        };
                    }
                    // Atualiza UI
                    document.getElementById('presenca-presentes').textContent = e.contagemPresentes ?? '--';
                    document.getElementById('presenca-ausentes').textContent = e.contagemAusentes ?? '--';
                })
                .listen('.LayoutTelaoAlterado', (e) => {
                    console.log('LayoutTelaoAlterado Event Received:', e);
                    // Atualiza estado global local
                    if (window.estadoGlobal) {
                        window.estadoGlobal.telao_layout = {
                            layout: e.layout,
                            dados: e.dados
                        };
                    }
                    mostrarLayout(e.layout, e.dados);
                })
                .listen('.VotacaoAberta', (e) => {
                    console.log('VotacaoAberta Event Received:', e);
                    const pautaData = e.pauta;
                    
                    // Atualiza estado global local
                    if (window.estadoGlobal) {
                        window.estadoGlobal.votacao_ativa = {
                            pauta_id: pautaData.id,
                            pauta_numero: pautaData.numero,
                            pauta_descricao: pautaData.descricao,
                            pauta_autor: pautaData.autor
                        };
                        window.estadoGlobal.contagem_votos = { sim: 0, nao: 0, abst: 0 };
                    }
                    
                    mostrarLayout('layout-votacao', pautaData);
                    document.getElementById('votacao-sim').textContent = '0';
                    document.getElementById('votacao-nao').textContent = '0';
                    document.getElementById('votacao-abst').textContent = '0';
                    const lista = document.getElementById('votacao-lista-nominal');
                    if (lista) lista.innerHTML = '';
                })
                .listen('.VotoRegistrado', (e) => {
                    console.log('VotoRegistrado Event Received:', e);
                    const lista = document.getElementById('votacao-lista-nominal');
                    if (!lista) return;

                    const id = e.vereadorId;
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
                .listen('.ContagemVotosAtualizada', (e) => {
                    console.log('ContagemVotosAtualizada Event Received:', e);
                    
                    // Atualiza estado global local
                    if (window.estadoGlobal) {
                        window.estadoGlobal.contagem_votos = {
                            sim: e.sim ?? 0,
                            nao: e.nao ?? 0,
                            abst: e.abst ?? 0
                        };
                    }
                    
                
                    atualizarContagemVotos(e);
                })
                .listen('VotacaoEncerrada', (e) => {
                    console.log('VotacaoEncerrada Event Received:', e);
                    
                    // Limpa estado global local
                    if (window.estadoGlobal) {
                        window.estadoGlobal.votacao_ativa = null;
                        window.estadoGlobal.contagem_votos = { sim: 0, nao: 0, abst: 0 };
                        window.estadoGlobal.telao_layout = { layout: 'layout-inicial', dados: null };
                    }
                    
                    mostrarLayout('layout-inicial');
                })
                .listen('.PalavraEstadoAlterado', (e) => {
                    console.log('PalavraEstadoAlterado Event Received:', e);
                    
                    if (window.estadoGlobal) {
                        window.estadoGlobal.palavra_ativa = e; // Atualiza o estado local
                    }
                    
                    if (e.vereador) {
                    document.getElementById('palavra-vereador').textContent = `Vereador(a): ${e.vereador.nome_parlamentar}`;
                    }
                    
                    if (e.status === 'iniciada' || e.status === 'retomada') {
                        iniciarCronometroTelao(e.segundosRestantes, e.timestampInicio);
                    } else if (e.status === 'pausada') {
                        pararCronometroTelao();
                        atualizarDisplayCronometro(e.segundosRestantes);
                    } else if (e.status === 'encerrada') {
                        pararCronometroTelao();
                        atualizarDisplayCronometro(0);
                        document.getElementById('palavra-vereador').textContent = 'Vereador(a): ...';
                        mostrarLayout('layout-inicial'); // Volta para a tela inicial
                    } else if (e.status === 'selecionado') {
                        // Apenas atualiza o nome, não inicia o timer
                        document.getElementById('palavra-vereador').textContent = `Vereador(a): ${e.vereador.nome_parlamentar}`;
                        atualizarDisplayCronometro(0);
                    }
                })
                .error((error) => {
                    console.error('WebSocket Error:', error);
                    
                });

            // Inicia sincronização periódica como backup
            sincronizarPeriodicamente();

            console.log("Listening on channel 'sessao-plenaria'...");
        });
    </script>
</body>
</html>