<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telão - Câmara Municipal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Meta tags para estado global -->
    <meta name="estado-global" content="{{ json_encode($estadoGlobal) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 50%, #1e40af 100%);
            color: white;
            overflow: hidden;
            height: 100vh;
        }
        
        /* Layout Principal - Câmera Central */
        .main-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header com Logo e Informações */
        .header-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(90deg, rgba(30, 58, 138, 0.95) 0%, rgba(55, 48, 163, 0.95) 100%);
            backdrop-filter: blur(15px);
            border-bottom: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            animation: slideDown 0.8s ease-out;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-placeholder {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            animation: pulse 2s infinite;
        }
        
        .session-info {
            text-align: center;
            flex: 1;
        }
        
        .session-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .session-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .time-info {
            text-align: right;
            font-size: 1.1rem;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        /* Área Central da Câmera */
        .camera-container {
            position: absolute;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }
        
        .camera-feed {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Overlays Contextuais */
        .overlay-container {
            position: absolute;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 50;
        }
        
        /* Overlay do Vereador Falando */
        .speaker-overlay {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 350px;
            border: 3px solid rgba(34, 197, 94, 0.6);
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.4);
            animation: slideInLeft 0.6s ease-out;
        }
        
        .speaker-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            border: 3px solid rgba(34, 197, 94, 0.7);
        }
        
        .speaker-info h3 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .speaker-info p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .speaker-timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #22c55e;
        }
        
        /* Overlay da Pauta */
        .agenda-overlay {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            max-width: 400px;
            border: 3px solid rgba(59, 130, 246, 0.6);
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.4);
            animation: slideInRight 0.6s ease-out;
        }
        
        .agenda-overlay h3 {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #60a5fa;
        }
        
        .agenda-item {
            margin-bottom: 0.75rem;
        }
        
        .agenda-label {
            font-size: 0.8rem;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .agenda-value {
            font-size: 1rem;
            font-weight: 600;
        }
        
        /* Placar de Presença */
        .presence-overlay {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1rem 1.5rem;
            border: 3px solid rgba(168, 85, 247, 0.6);
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.4);
            animation: slideInLeft 0.6s ease-out;
        }
        
        .presence-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            text-align: center;
        }
        
        .presence-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .presence-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .presence-label {
            font-size: 0.8rem;
            opacity: 0.8;
            text-transform: uppercase;
        }
        
        .presence-present .presence-number { color: #22c55e; }
        .presence-absent .presence-number { color: #ef4444; }
        
        /* Layout de Votação */
        .voting-layout {
            position: absolute;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            display: grid;
            grid-template-columns: 1fr 350px;
            grid-template-rows: auto 1fr;
            gap: 1rem;
            padding: 1rem;
            grid-template-areas: 
                "main-content camera"
                "main-content sidebar";
        }
        
        .voting-main-content {
            grid-area: main-content;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .voting-camera {
            grid-area: camera;
            position: relative;
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
            height: 200px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .voting-sidebar {
            grid-area: sidebar;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .voting-pauta-info {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 3px solid rgba(168, 85, 247, 0.6);
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.4);
            animation: fadeInUp 0.4s ease-out;
        }
        
        .voting-pauta-info h3 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
            color: #a855f7;
        }
        
        .pauta-details {
            display: grid;
            gap: 0.75rem;
        }
        
        .pauta-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .pauta-detail-item:last-child {
            border-bottom: none;
        }
        
        .pauta-detail-label {
            font-size: 0.9rem;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pauta-detail-value {
            font-size: 1rem;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-wrap: break-word;
        }
        
        .voting-parliamentarians {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            flex: 1;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .voting-parliamentarians h3 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .voting-scoreboard {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.6s ease-out;
        }
        
        .score-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .score-item {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 2px solid;
        }
        
        .score-sim { 
            background: rgba(34, 197, 94, 0.1);
            border-color: #22c55e;
        }
        
        .score-nao { 
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
        }
        
        .score-abst { 
            background: rgba(245, 158, 11, 0.1);
            border-color: #f59e0b;
        }
        
        .score-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .score-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            opacity: 0.8;
        }
        
        .voting-list {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            padding: 1rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            flex: 1;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .voting-list h3 {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .parliamentarians-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            max-height: calc(100% - 3rem);
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .parliamentarian-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            border-left: 4px solid #6b7280;
            transition: all 0.3s ease;
            min-height: 60px;
        }
        
        .parliamentarian-photo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .parliamentarian-info {
            flex: 1;
            min-width: 0;
        }
        
        .parliamentarian-name {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .parliamentarian-party {
            font-size: 0.65rem;
            opacity: 0.7;
        }
        
        .vote-status {
            font-size: 0.7rem;
            font-weight: bold;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            background: rgba(107, 114, 128, 0.3);
            white-space: nowrap;
        }
        
        /* Estados de Voto */
        .vote-sim {
            border-left-color: #22c55e !important;
            background: rgba(34, 197, 94, 0.1) !important;
        }
        
        .vote-sim .vote-status {
            background: rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .vote-nao {
            border-left-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.1) !important;
        }
        
        .vote-nao .vote-status {
            background: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .vote-abst {
            border-left-color: #f59e0b !important;
            background: rgba(245, 158, 11, 0.1) !important;
        }
        
        .vote-abst .vote-status {
            background: rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }
        
        /* Animações */
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.3); }
            50% { box-shadow: 0 0 30px rgba(34, 197, 94, 0.6); }
        }
        
        /* Scrollbar personalizada */
        .parliamentarians-grid::-webkit-scrollbar {
            width: 4px;
        }
        
        .parliamentarians-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
        
        .parliamentarians-grid::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }
        
        /* Estados de Layout */
        .hidden { display: none !important; }
        
        /* Responsividade */
        @media (max-width: 1200px) {
            .voting-layout {
                grid-template-columns: 1fr 350px;
            }
        }
        
        @media (max-width: 768px) {
            .header-bar {
                padding: 0 1rem;
                height: 60px;
            }
            
            .session-title {
                font-size: 1.2rem;
            }
            
            .speaker-overlay,
            .agenda-overlay,
            .presence-overlay {
                position: relative;
                margin: 1rem;
            }
            
            .voting-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Indicador de conexão -->
        <div id="connection-status" class="connection-status"></div>
        
        <!-- Header Fixo -->
        <div class="header-bar">
            <div class="logo-section">
                <div class="logo-placeholder">CM</div>
                <div>
                    <div class="font-bold">Câmara Municipal</div>
                    <div class="text-sm opacity-75">Rio Verde - GO</div>
                </div>
            </div>
            
            <div class="session-info">
                <div class="session-title" id="session-title">5ª Sessão Ordinária</div>
                <div class="session-subtitle" id="session-date">Fevereiro de 2025</div>
            </div>
            
            <div class="time-info">
                <div id="current-time">00:00:00</div>
                <div class="text-sm opacity-75">Ao Vivo</div>
            </div>
        </div>
        
        <!-- Layout Normal - Câmera Central -->
        <div id="layout-normal" class="camera-container">
            <video id="camera-feed" class="camera-feed" autoplay muted></video>
            
            <!-- Overlays -->
            <div class="overlay-container">
                <!-- Overlay de Presença -->
                <div id="presence-overlay" class="presence-overlay">
                    <div class="presence-grid">
                        <div class="presence-item presence-present">
                            <div class="presence-number" id="presenca-presentes">--</div>
                            <div class="presence-label">Presentes</div>
                        </div>
                        <div class="presence-item presence-absent">
                            <div class="presence-number" id="presenca-ausentes">--</div>
                            <div class="presence-label">Ausentes</div>
                        </div>
                    </div>
                </div>
                
                <!-- Overlay da Pauta -->
                <div id="agenda-overlay" class="agenda-overlay hidden">
                    <h3>Pauta em Discussão</h3>
                    <div class="agenda-item">
                        <div class="agenda-label">Número</div>
                        <div class="agenda-value" id="pauta-numero">--</div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-label">Descrição</div>
                        <div class="agenda-value" id="pauta-descricao">--</div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-label">Autor</div>
                        <div class="agenda-value" id="pauta-autor">--</div>
                    </div>
                </div>
                
                <!-- Overlay do Vereador Falando -->
                <div id="speaker-overlay" class="speaker-overlay hidden">
                    <div class="speaker-photo" id="speaker-photo">V</div>
                    <div class="speaker-info">
                        <h3 id="speaker-name">Vereador(a)</h3>
                        <p id="speaker-party">Partido</p>
                        <div class="speaker-timer" id="speaker-timer">05:00</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Layout de Votação -->
        <div id="layout-voting" class="voting-layout hidden">
            <!-- Conteúdo Principal (Lista de Vereadores e Informações da Pauta) -->
            <div class="voting-main-content">
                <!-- Informações da Pauta em Votação -->
                <div class="voting-pauta-info">
                    <h3>Pauta em Votação</h3>
                    <div class="pauta-details">
                        <div class="pauta-detail-item">
                            <div class="pauta-detail-label">Número</div>
                            <div class="pauta-detail-value" id="votacao-pauta-numero">--</div>
                        </div>
                        <div class="pauta-detail-item">
                            <div class="pauta-detail-label">Descrição</div>
                            <div class="pauta-detail-value" id="votacao-pauta-descricao">--</div>
                        </div>
                        <div class="pauta-detail-item">
                            <div class="pauta-detail-label">Autor</div>
                            <div class="pauta-detail-value" id="votacao-pauta-autor">--</div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Parlamentares -->
                <div class="voting-parliamentarians">
                    <h3>Parlamentares</h3>
                    <div id="parliamentarians-grid" class="parliamentarians-grid">
                        <!-- Lista será preenchida dinamicamente -->
                    </div>
                </div>
            </div>
            
            <!-- Câmera (Canto Superior Direito) -->
            <div class="voting-camera">
                <video id="voting-camera-feed" class="camera-feed" autoplay muted></video>
            </div>
            
            <!-- Sidebar com Placar -->
            <div class="voting-sidebar">
                <!-- Placar de Votação -->
                <div class="voting-scoreboard">
                    <h3 class="text-center font-bold mb-3">Placar da Votação</h3>
                    <div class="score-grid">
                        <div class="score-item score-sim">
                            <div class="score-number" id="votacao-sim">0</div>
                            <div class="score-label">SIM</div>
                        </div>
                        <div class="score-item score-nao">
                            <div class="score-number" id="votacao-nao">0</div>
                            <div class="score-label">NÃO</div>
                        </div>
                        <div class="score-item score-abst">
                            <div class="score-number" id="votacao-abst">0</div>
                            <div class="score-label">ABST</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variáveis globais
        let layoutAtual = 'layout-normal';
        let cameraStream = null;
        let estadoGlobal = {};
        let cronometroPalavra = null;
        let connectionStatus = document.getElementById('connection-status');
        let isConnected = false;
        
        // Função para atualizar o relógio
        function atualizarRelogio() {
            const agora = new Date();
            const horas = agora.getHours().toString().padStart(2, '0');
            const minutos = agora.getMinutes().toString().padStart(2, '0');
            const segundos = agora.getSeconds().toString().padStart(2, '0');
            
            const elementoRelogio = document.getElementById('current-time');
            if (elementoRelogio) {
                elementoRelogio.textContent = `${horas}:${minutos}:${segundos}`;
            }
        }
        
        // Função para atualizar status de conexão
        function atualizarStatusConexao(conectado) {
            isConnected = conectado;
            if (connectionStatus) {
                if (conectado) {
                    connectionStatus.classList.remove('disconnected');
                    connectionStatus.title = 'Conectado ao servidor';
                } else {
                    connectionStatus.classList.add('disconnected');
                    connectionStatus.title = 'Desconectado do servidor';
                }
            }
        }
        
        // Função para inicializar a câmera
        async function inicializarCamera() {
            try {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    
                    const cameraFeed = document.getElementById('camera-feed');
                    const votingCameraFeed = document.getElementById('voting-camera-feed');
                    
                    if (cameraFeed) {
                        cameraFeed.srcObject = cameraStream;
                    }
                    if (votingCameraFeed) {
                        votingCameraFeed.srcObject = cameraStream;
                    }
                }
            } catch (error) {
                console.error('Erro ao acessar a câmera:', error);
            }
        }
        
        // Função para mostrar layout
        function mostrarLayout(layout, dados = null) {
            console.log(`Mudando para layout: ${layout}`, dados);
            
            // Remove animações anteriores
            document.getElementById('layout-normal').classList.remove('fade-in');
            document.getElementById('layout-voting').classList.remove('fade-in');
            
            // Esconde todos os layouts
            document.getElementById('layout-normal').classList.add('hidden');
            document.getElementById('layout-voting').classList.add('hidden');
            
            // Mostra o layout solicitado
            const layoutElement = document.getElementById(layout);
            if (layoutElement) {
                layoutElement.classList.remove('hidden');
                // Força reflow para garantir que a animação funcione
                layoutElement.offsetHeight;
                layoutElement.classList.add('fade-in');
                layoutAtual = layout;
                
                console.log(`Layout ${layout} ativado com sucesso`);
            } else {
                console.error(`Layout ${layout} não encontrado, usando layout-normal`);
                const normalLayout = document.getElementById('layout-normal');
                normalLayout.classList.remove('hidden');
                normalLayout.offsetHeight;
                normalLayout.classList.add('fade-in');
                layoutAtual = 'layout-normal';
            }
            
            // Atualiza overlays baseado no layout e dados
            atualizarOverlays(layout, dados);
        }
        
        // Função para atualizar overlays
        function atualizarOverlays(layout, dados) {
            console.log('Atualizando overlays para layout:', layout, 'com dados:', dados);
            
            // Remove animações anteriores de todos os overlays
            const agendaOverlay = document.getElementById('agenda-overlay');
            const speakerOverlay = document.getElementById('speaker-overlay');
            
            agendaOverlay.classList.remove('slide-up');
            speakerOverlay.classList.remove('slide-up');
            
            // Esconde todos os overlays primeiro
            agendaOverlay.classList.add('hidden');
            speakerOverlay.classList.add('hidden');
            
            if (layout === 'layout-normal') {
                // Mostra overlay da pauta se houver dados
                if (dados && (dados.numero || dados.descricao)) {
                    document.getElementById('pauta-numero').textContent = dados.numero || '--';
                    document.getElementById('pauta-descricao').textContent = dados.descricao || '--';
                    document.getElementById('pauta-autor').textContent = dados.autor || '--';
                    
                    agendaOverlay.classList.remove('hidden');
                    // Força reflow para garantir que a animação funcione
                    agendaOverlay.offsetHeight;
                    agendaOverlay.classList.add('slide-up');
                    
                    console.log('Overlay da pauta ativado');
                }
                
                // SEMPRE verifica se há vereador falando ativo, independente dos dados passados
                // Prioridade: dados passados > estado global
                if (dados && dados.vereador) {
                    mostrarOverlayVereador(dados.vereador);
                } else if (estadoGlobal.palavra_ativa && estadoGlobal.palavra_ativa.vereador) {
                    console.log('Mantendo overlay do vereador ativo do estado global');
                    mostrarOverlayVereador(estadoGlobal.palavra_ativa.vereador);
                    
                    // Reinicia cronômetro se houver tempo restante
                    const tempo = estadoGlobal.palavra_ativa.tempo_restante || 
                                 estadoGlobal.palavra_ativa.segundosRestantes || 
                                 estadoGlobal.palavra_ativa.segundos_restantes;
                    if (tempo) {
                        const timestamp = estadoGlobal.palavra_ativa.timestamp_inicio;
                        iniciarCronometroPalavra(tempo, timestamp);
                    }
                }
            } else if (layout === 'layout-voting') {
                // Atualiza informações da votação
                if (dados) {
                    document.getElementById('votacao-pauta-numero').textContent = dados.numero || '--';
                    document.getElementById('votacao-pauta-descricao').textContent = dados.descricao || '--';
                    document.getElementById('votacao-pauta-autor').textContent = dados.autor || '--';
                    console.log('Informações da pauta na votação atualizadas:', dados);
                }
                
                // Carrega lista de vereadores
                console.log('Carregando lista de vereadores para votação...');
                carregarListaVereadores();
            }
        }
        
        // Função para mostrar overlay do vereador falando
        function mostrarOverlayVereador(vereador, tempo = null) {
            console.log('Mostrando overlay do vereador:', vereador);
            
            const overlay = document.getElementById('speaker-overlay');
            const photo = document.getElementById('speaker-photo');
            const name = document.getElementById('speaker-name');
            const party = document.getElementById('speaker-party');
            const timer = document.getElementById('speaker-timer');
            
            // Remove animação anterior
            overlay.classList.remove('slide-up');
            
            // Atualiza informações
            photo.textContent = vereador.nome_parlamentar ? vereador.nome_parlamentar.charAt(0).toUpperCase() : 'V';
            name.textContent = vereador.nome_parlamentar || 'Vereador(a)';
            party.textContent = vereador.partido || 'Partido';
            
            if (tempo !== null) {
                const minutos = Math.floor(tempo / 60);
                const segundos = tempo % 60;
                timer.textContent = `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
            }
            
            overlay.classList.remove('hidden');
            // Força reflow para garantir que a animação funcione
            overlay.offsetHeight;
            overlay.classList.add('slide-up');
            
            console.log('Overlay do vereador ativado');
        }
        
        // Função para iniciar cronômetro da palavra
        function iniciarCronometroPalavra(tempoInicial, timestampInicio = null) {
            // Para cronômetro anterior se existir
            if (cronometroPalavra) {
                clearInterval(cronometroPalavra);
            }
            
            const timer = document.getElementById('speaker-timer');
            let tempoRestante = tempoInicial;
            
            // CORREÇÃO: Verifica se o status está pausado - se estiver, não inicia o cronômetro
            if (estadoGlobal.palavra_ativa && estadoGlobal.palavra_ativa.status === 'paused') {
                console.log('Timer pausado - não iniciando cronômetro');
                // Apenas atualiza o display com o tempo pausado
                const minutos = Math.floor(tempoRestante / 60);
                const segundos = tempoRestante % 60;
                timer.textContent = `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
                timer.style.color = tempoRestante <= 30 ? '#ff4444' : '#ffffff';
                return; // Sai da função sem iniciar o cronômetro
            }
            
            // Se há timestamp de início, calcula o tempo real decorrido
            if (timestampInicio && estadoGlobal.palavra_ativa && estadoGlobal.palavra_ativa.status === 'running') {
                const agora = Math.floor(Date.now() / 1000);
                const tempoDecorrido = agora - timestampInicio;
                tempoRestante = Math.max(0, tempoInicial - tempoDecorrido);
                console.log(`Sincronizando timer: ${tempoDecorrido}s decorridos, ${tempoRestante}s restantes`);
            }
            
            // Função para atualizar display
            function atualizarDisplay(segundos) {
                if (segundos < 0) segundos = 0;
                const minutos = Math.floor(segundos / 60);
                const segs = segundos % 60;
                timer.textContent = `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
                
                // Muda cor quando restam menos de 30 segundos
                if (segundos <= 30) {
                    timer.style.color = '#ff4444';
                } else {
                    timer.style.color = '#ffffff';
                }
            }
            
            // Atualiza display inicial
            atualizarDisplay(tempoRestante);
            
            // Inicia contagem regressiva
            cronometroPalavra = setInterval(() => {
                tempoRestante--;
                
                if (tempoRestante >= 0) {
                    atualizarDisplay(tempoRestante);
                } else {
                    // Tempo esgotado
                    timer.textContent = '00:00';
                    timer.style.color = '#ff4444';
                    clearInterval(cronometroPalavra);
                    cronometroPalavra = null;
                }
            }, 1000);
            
            console.log(`Cronômetro iniciado com ${tempoInicial} segundos (restantes: ${tempoRestante})`);
        }
        
        // Função para carregar lista de vereadores na votação
        async function carregarListaVereadores() {
            try {
                const response = await fetch('/api/vereadores-votacao');
                const data = await response.json();
                
                const grid = document.getElementById('parliamentarians-grid');
                grid.innerHTML = '';
                
                data.vereadores.forEach(vereador => {
                    const item = document.createElement('div');
                    item.className = 'parliamentarian-item';
                    item.id = `vereador-${vereador.id}`;
                    
                    let votoClass = '';
                    let votoTexto = 'AGUARDANDO';
                    
                    if (vereador.voto) {
                        if (vereador.voto === 'sim') {
                            votoClass = 'vote-sim';
                            votoTexto = 'SIM';
                        } else if (vereador.voto === 'nao') {
                            votoClass = 'vote-nao';
                            votoTexto = 'NÃO';
                        } else if (vereador.voto === 'abst') {
                            votoClass = 'vote-abst';
                            votoTexto = 'ABSTENÇÃO';
                        }
                        item.classList.add(votoClass);
                    }
                    
                    item.innerHTML = `
                        <div class="parliamentarian-photo">
                            ${vereador.nome_parlamentar ? vereador.nome_parlamentar.charAt(0).toUpperCase() : 'V'}
                        </div>
                        <div class="parliamentarian-info">
                            <div class="parliamentarian-name">${vereador.nome_parlamentar}</div>
                            <div class="parliamentarian-party">${vereador.partido}</div>
                        </div>
                        <div class="vote-status">${votoTexto}</div>
                    `;
                    
                    grid.appendChild(item);
                });
                
                console.log(`Carregados ${data.vereadores.length} vereadores na lista`);
            } catch (error) {
                console.error('Erro ao carregar vereadores:', error);
            }
        }
        
        // Função para sincronizar estado global
        async function sincronizarEstadoGlobal() {
            try {
                // CORREÇÃO: Busca estado atual via AJAX em vez de meta tag estática
                const response = await fetch('/api/estado-global');
                if (response.ok) {
                    const result = await response.json();
                    if (result.status === 'success' && result.data) {
                        estadoGlobal = result.data;
                        console.log('Estado global sincronizado via API:', estadoGlobal);
                    } else {
                        throw new Error('Resposta da API inválida');
                    }
                } else {
                    // Fallback para meta tag se API falhar
                    const metaTag = document.querySelector('meta[name="estado-global"]');
                    if (metaTag) {
                        estadoGlobal = JSON.parse(metaTag.getAttribute('content'));
                        console.log('Estado global sincronizado via meta tag (fallback):', estadoGlobal);
                    }
                }
                
                // Atualiza presença
                const presentes = (estadoGlobal.presenca && estadoGlobal.presenca.presentes !== undefined) 
                    ? estadoGlobal.presenca.presentes 
                    : '--';
                const ausentes = (estadoGlobal.presenca && estadoGlobal.presenca.ausentes !== undefined) 
                    ? estadoGlobal.presenca.ausentes 
                    : '--';
                
                document.getElementById('presenca-presentes').textContent = presentes;
                document.getElementById('presenca-ausentes').textContent = ausentes;
                
                console.log(`Presença atualizada: ${presentes} presentes, ${ausentes} ausentes`);
                
                // Atualiza contagem de votos se houver votação ativa
                if (estadoGlobal.contagem_votos) {
                    const sim = estadoGlobal.contagem_votos.sim || '0';
                    const nao = estadoGlobal.contagem_votos.nao || '0';
                    const abst = estadoGlobal.contagem_votos.abst || '0';
                    
                    document.getElementById('votacao-sim').textContent = sim;
                    document.getElementById('votacao-nao').textContent = nao;
                    document.getElementById('votacao-abst').textContent = abst;
                    
                    console.log(`Contagem de votos atualizada: ${sim} sim, ${nao} não, ${abst} abstenções`);
                }
                
                console.log('Estado global sincronizado com sucesso');
                
            } catch (error) {
                console.error('Erro ao sincronizar estado global:', error);
                // Fallback para meta tag em caso de erro
                const metaTag = document.querySelector('meta[name="estado-global"]');
                if (metaTag) {
                    try {
                        estadoGlobal = JSON.parse(metaTag.getAttribute('content'));
                        console.log('Estado global sincronizado via meta tag (erro fallback):', estadoGlobal);
                    } catch (parseError) {
                        console.error('Erro ao parsear meta tag:', parseError);
                    }
                }
            }
        }
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Inicializando telão...');
            
            // Inicia relógio
            atualizarRelogio();
            setInterval(atualizarRelogio, 1000);
            
            // Inicializa câmera
            inicializarCamera();
            
            // Sincroniza estado global
            sincronizarEstadoGlobal();
            
            // Layout inicial - CORRIGIDO: Garantir que sempre mostre um layout
            const layoutInicial = @json($layoutInicial ?? 'layout-normal');
            const dadosIniciais = @json($dadosPautaInicial ?? null);
            
            console.log('Layout inicial:', layoutInicial, 'Dados:', dadosIniciais);
            
            // Aplica layout inicial após sincronizar estado
            setTimeout(() => {
                // Força mostrar o layout normal se não houver layout específico
                if (!layoutInicial || layoutInicial === 'layout-inicial') {
                    mostrarLayout('layout-normal', dadosIniciais);
                } else {
                    mostrarLayout(layoutInicial, dadosIniciais);
                }
                
                // Agora verifica se há estados ativos que devem sobrescrever o layout inicial
                 // CORREÇÃO: Palavra ativa tem PRIORIDADE MÁXIMA sobre votação
                 if (estadoGlobal.palavra_ativa && estadoGlobal.palavra_ativa.vereador) {
                     console.log('Palavra ativa detectada após layout inicial, adicionando overlay');
                     mostrarOverlayVereador(estadoGlobal.palavra_ativa.vereador);
                     const tempo = estadoGlobal.palavra_ativa.tempo_restante || 
                                  estadoGlobal.palavra_ativa.segundosRestantes || 
                                  estadoGlobal.palavra_ativa.segundos_restantes;
                     if (tempo) {
                         const timestamp = estadoGlobal.palavra_ativa.timestamp_inicio;
                         iniciarCronometroPalavra(tempo, timestamp);
                     }
                 } else if (estadoGlobal.votacao_ativa) {
                     console.log('Votação ativa detectada após layout inicial (sem palavra ativa), mudando para votação');
                     mostrarLayout('layout-voting', {
                         numero: estadoGlobal.votacao_ativa.pauta_numero,
                         descricao: estadoGlobal.votacao_ativa.pauta_descricao,
                         autor: estadoGlobal.votacao_ativa.pauta_autor
                     });
                 }
            }, 200);
            
            // Configura WebSocket se disponível
            if (window.Echo) {
                console.log('Configurando listeners WebSocket...');
                
                window.Echo.channel('sessao-plenaria')
                    .listen('.PresencaAtualizada', (e) => {
                        console.log('PresencaAtualizada:', e);
                        
                        const presentes = (e.contagemPresentes !== undefined && e.contagemPresentes !== null) 
                            ? e.contagemPresentes 
                            : '--';
                        const ausentes = (e.contagemAusentes !== undefined && e.contagemAusentes !== null) 
                            ? e.contagemAusentes 
                            : '--';
                            
                        document.getElementById('presenca-presentes').textContent = presentes;
                        document.getElementById('presenca-ausentes').textContent = ausentes;
                        
                        console.log(`Presença atualizada via evento: ${presentes} presentes, ${ausentes} ausentes`);
                    })
                    .listen('.LayoutTelaoAlterado', async (e) => {
                        console.log('LayoutTelaoAlterado:', e);
                        
                        // CORREÇÃO: Ao receber evento de mudança de layout, 
                        // sincroniza com estado global para preservar vereador falando
                        await sincronizarEstadoGlobal();
                        mostrarLayout(e.layout, e.dados);
                    })
                    .listen('.VotacaoAberta', (e) => {
                        console.log('VotacaoAberta:', e);
                        mostrarLayout('layout-voting', e.pauta);
                        
                        // Zera contadores
                        document.getElementById('votacao-sim').textContent = '0';
                        document.getElementById('votacao-nao').textContent = '0';
                        document.getElementById('votacao-abst').textContent = '0';
                    })
                    .listen('.VotoRegistrado', (e) => {
                        console.log('VotoRegistrado:', e);
                        
                        // Atualiza item específico na lista
                        const item = document.getElementById(`vereador-${e.vereadorId}`);
                        if (item) {
                            // Remove classes de voto anteriores
                            item.classList.remove('vote-sim', 'vote-nao', 'vote-abst');
                            
                            const votoStatus = item.querySelector('.vote-status');
                            const voto = (e.voto || '').toLowerCase();
                            
                            if (voto === 'sim') {
                                item.classList.add('vote-sim');
                                votoStatus.textContent = 'SIM';
                            } else if (voto === 'nao') {
                                item.classList.add('vote-nao');
                                votoStatus.textContent = 'NÃO';
                            } else if (voto === 'abst') {
                                item.classList.add('vote-abst');
                                votoStatus.textContent = 'ABSTENÇÃO';
                            }
                        }
                    })
                    .listen('.ContagemVotosAtualizada', (e) => {
                        console.log('ContagemVotosAtualizada:', e);
                        
                        // Atualiza placar de votação
                        const sim = document.getElementById('votacao-sim');
                        const nao = document.getElementById('votacao-nao');
                        const abst = document.getElementById('votacao-abst');
                        
                        if (sim) sim.textContent = e.contagem?.sim ?? e.sim ?? '0';
                        if (nao) nao.textContent = e.contagem?.nao ?? e.nao ?? '0';
                        if (abst) abst.textContent = e.contagem?.abst ?? e.abst ?? '0';
                        
                        // Atualiza lista de vereadores se estivermos no layout de votação
                        if (layoutAtual === 'layout-voting') {
                            carregarListaVereadores();
                        }
                    })
                        .listen('.VotacaoEncerrada', (e) => {
                        console.log('VotacaoEncerrada:', e);
                        mostrarLayout('layout-normal');
                    })
                    .listen('.PalavraEstadoAlterado', (e) => {
                        console.log('PalavraEstadoAlterado:', e);
                        
                        if (e.status === 'iniciada' && e.vereador) {
                            // Usa layout-normal com dados do vereador para mostrar o overlay
                            mostrarLayout('layout-normal', { vereador: e.vereador });
                            
                            // Inicia cronômetro se houver tempo
                            if (e.segundosRestantes) {
                                iniciarCronometroPalavra(e.segundosRestantes, e.timestampInicio);
                            }
                        } else if (e.status === 'pausada') {
                            // CORREÇÃO: Pausa o cronômetro em tempo real
                            console.log('Pausando cronômetro no telão');
                            if (cronometroPalavra) {
                                clearInterval(cronometroPalavra);
                                cronometroPalavra = null;
                            }
                            // Mantém o overlay do vereador visível
                        } else if (e.status === 'retomada' && e.vereador) {
                            // CORREÇÃO: Retoma o cronômetro em tempo real
                            console.log('Retomando cronômetro no telão');
                            if (e.segundosRestantes) {
                                iniciarCronometroPalavra(e.segundosRestantes, e.timestampInicio);
                            }
                        } else if (e.status === 'encerrada') {
                            // CORREÇÃO: Ao encerrar palavra, remove apenas o overlay do vereador
                            // mas mantém a pauta se ela estiver ativa
                            console.log('Encerrando palavra - removendo overlay do vereador');
                            
                            // Para o cronômetro
                            if (cronometroPalavra) {
                                clearInterval(cronometroPalavra);
                                cronometroPalavra = null;
                            }
                            
                            // Remove apenas o overlay do vereador
                            const speakerOverlay = document.getElementById('speaker-overlay');
                            if (speakerOverlay) {
                                speakerOverlay.classList.add('hidden');
                            }
                            
                            // Verifica se há pauta ativa para manter no telão
                            if (estadoGlobal.telao_layout && estadoGlobal.telao_layout.dados && estadoGlobal.telao_layout.dados.numero) {
                                console.log('Mantendo pauta ativa no telão:', estadoGlobal.telao_layout.dados.numero);
                                // Não muda o layout, apenas remove o overlay do vereador
                            } else {
                                // Se não há pauta ativa, volta para layout normal
                                mostrarLayout('layout-normal');
                            }
                        }
                    });
                
                // Monitorar conexão
                 if (window.Echo && window.Echo.connector && window.Echo.connector.socket) {
                     window.Echo.connector.socket.on('connect', () => {
                         console.log('Conectado ao WebSocket');
                         atualizarStatusConexao(true);
                     });
                     
                     window.Echo.connector.socket.on('disconnect', () => {
                         console.log('Desconectado do WebSocket');
                         atualizarStatusConexao(false);
                     });
                     
                     // Status inicial
                     atualizarStatusConexao(window.Echo.connector.socket.connected || false);
                 } else {
                     console.log('WebSocket não disponível, assumindo conexão via polling');
                     atualizarStatusConexao(true);
                 }
            } else {
                console.error('Echo não está disponível');
                atualizarStatusConexao(false);
            }
            
            console.log('Telão inicializado com sucesso!');
        });
    </script>
</body>
</html>