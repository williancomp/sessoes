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

    <script type="module">
        // Inicialização do Echo e listeners virão aqui no passo 5
        console.log('Telão View Loaded');

        // Exemplo: Mostrar layout inicial por padrão
        document.getElementById('layout-inicial').style.display = 'flex';

        // Função auxiliar para mostrar apenas um layout e esconder os outros
        function showLayout(layoutId) {
            const layouts = ['layout-inicial', 'layout-camera', 'layout-pauta', 'layout-votacao', 'layout-palavra'];
            layouts.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.style.display = (id === layoutId) ? 'flex' : 'none'; // Use 'flex' para centralizar
                }
            });
            console.log(`Showing layout: ${layoutId}`);
        }

        // Conecta ao canal público da sessão plenária
        window.Echo.channel('sessao-plenaria')
            .listen('PresencaAtualizada', (e) => {
                console.log('PresencaAtualizada Event Received:', e);
                document.getElementById('presenca-presentes').textContent = e.contagemPresentes ?? '--';
                document.getElementById('presenca-ausentes').textContent = e.contagemAusentes ?? '--';
            })
            .listen('LayoutTelaoAlterado', (e) => {
                console.log('LayoutTelaoAlterado Event Received:', e);
                // Aqui adicionaremos a lógica para atualizar os dados de cada layout
                // Por enquanto, apenas muda o layout visível
                showLayout(e.layout); // Mostra o layout solicitado pelo evento

                // Lógica específica para ativar/desativar câmera
                const videoElement = document.getElementById('camera-feed');
                if (e.layout === 'layout-camera') {
                    // Tenta acessar a câmera
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({ video: true })
                            .then(function(stream) {
                                videoElement.srcObject = stream;
                                videoElement.play();
                            })
                            .catch(function(err) {
                                console.error("Erro ao acessar a câmera: ", err);
                                // Poderia mostrar uma mensagem de erro no layout da câmera
                            });
                    } else {
                        console.error("getUserMedia não suportado neste navegador.");
                    }
                } else {
                    // Para qualquer outro layout, para a câmera para liberar o recurso
                    if (videoElement.srcObject) {
                        videoElement.srcObject.getTracks().forEach(track => track.stop());
                        videoElement.srcObject = null;
                    }
                }
            })
            // Adicione mais listeners .listen(...) aqui para VotacaoAberta, VotoRegistrado, etc.
            .error((error) => {
                console.error('WebSocket Error:', error);
            });

        console.log("Listening on channel 'sessao-plenaria'...");

        // --- FIM DO CÓDIGO ECHO ---
    </script>
</body>
</html>