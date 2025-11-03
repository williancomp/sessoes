import { createApp } from 'vue'
import TelaoApp from './components/TelaoApp.vue'
import '../css/app.css'

// Configuração do Echo para WebSockets (se disponível)
let echo = null
try {
    if (window.Echo) {
        echo = window.Echo
    }
} catch (e) {
    console.log('Echo não disponível, usando polling')
}

// Cria a aplicação Vue
const app = createApp(TelaoApp)

// Configurações globais
app.config.globalProperties.$echo = echo

// Monta a aplicação
app.mount('#telao-app')

// Configuração adicional para WebSockets se disponível
if (echo) {
    // Escuta mudanças no layout do telão
    echo.channel('telao')
        .listen('LayoutTelaoAlterado', (e) => {
            console.log('Layout do telão alterado:', e)
            // O componente Vue irá sincronizar automaticamente
        })
        .listen('PalavraEstadoAlterado', (e) => {
            console.log('Estado da palavra alterado:', e)
            // O componente Vue irá sincronizar automaticamente
        })
}

console.log('Telão Vue.js inicializado')