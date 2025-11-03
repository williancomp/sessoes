# Telão Vue.js - Câmara Municipal

## 🚀 Nova Implementação

A rota `/telao` foi recriada usando **Vue.js** para melhor performance e reatividade em tempo real.

## 📍 Rotas Disponíveis

- **Telão Original (Blade)**: `/telao`
- **Telão Vue.js (Novo)**: `/telao-vue` ⭐

## ✨ Vantagens da Versão Vue.js

### 🔄 Reatividade Automática
- Atualizações em tempo real sem reload da página
- Sincronização automática a cada 5 segundos
- Interface responsiva e fluida

### ⚡ Performance Otimizada
- Carregamento mais rápido
- Menor uso de recursos
- Atualizações incrementais (apenas o que mudou)

### 🎨 Interface Moderna
- Design responsivo e elegante
- Animações suaves
- Estados de loading e erro

### 🔌 Conectividade Inteligente
- Indicador de status de conexão
- Reconexão automática em caso de falha
- Fallback para polling se WebSockets não estiverem disponíveis

## 🏗️ Arquitetura

### Frontend (Vue.js)
```
resources/js/
├── components/
│   └── TelaoApp.vue          # Componente principal
├── telao.js                  # Inicialização da app
└── ...

resources/views/
└── telao-vue.blade.php       # Template base
```

### Backend (Laravel)
```
app/Http/Controllers/
└── TelaoApiController.php    # API para dados do telão

routes/
├── api.php                   # Rotas da API (/api/telao/*)
└── web.php                   # Rota principal (/telao-vue)
```

## 📡 API Endpoints

### `GET /api/telao/estado`
Retorna o estado completo do telão:
```json
{
  "layout": "layout-normal",
  "dados": {
    "id": 1,
    "numero": "PLC 01/2025",
    "descricao": "<p>Descrição da pauta</p>",
    "autor": "Vereador X"
  },
  "palavra_ativa": {
    "vereador": {...},
    "status": "running",
    "segundos_restantes": 300
  },
  "timestamp": "2025-11-03T00:06:28.600961Z"
}
```

### `GET /api/telao/layout/{layout}`
Retorna dados específicos para um layout.

## 🔧 Funcionalidades

### ✅ Implementadas
- [x] Exibição de pauta ativa
- [x] Vereador com a palavra
- [x] Timer da palavra (com pause/resume)
- [x] Layout de votação
- [x] Sincronização automática
- [x] Estados de loading/erro
- [x] Indicador de conexão
- [x] Design responsivo

### 🚧 Futuras Melhorias
- [ ] WebSockets em tempo real (configurado, mas opcional)
- [ ] Notificações push
- [ ] Modo offline
- [ ] Configurações personalizáveis

## 🛠️ Desenvolvimento

### Compilar Assets
```bash
# Desenvolvimento (watch mode)
npm run dev

# Produção
npm run build
```

### Estrutura de Estados
O componente Vue gerencia estados reativos:
- `loading`: Estado de carregamento
- `error`: Mensagens de erro
- `conectado`: Status da conexão
- `estado`: Dados do telão (layout, dados, palavra_ativa)
- `agora`: Relógio em tempo real
- `tempoAtual`: Timer da palavra

### Ciclo de Atualização
1. **Inicialização**: Busca estado inicial da API
2. **Sincronização**: Atualiza a cada 5 segundos
3. **Reatividade**: Interface atualiza automaticamente
4. **Timer**: Contagem regressiva da palavra (1 segundo)
5. **Relógio**: Atualização da data/hora (1 segundo)

## 🎯 Como Usar

1. Acesse `/telao-vue` no navegador
2. O telão carregará automaticamente
3. Mudanças no painel administrativo aparecerão em tempo real
4. Em caso de erro, clique em "Tentar Novamente"

## 🔄 Migração

A versão original (`/telao`) continua funcionando. Para migrar completamente:

1. Teste a nova versão em `/telao-vue`
2. Confirme que todas as funcionalidades estão ok
3. Atualize os links/bookmarks para a nova rota
4. Opcionalmente, redirecione `/telao` para `/telao-vue`

---

**Desenvolvido com ❤️ usando Vue.js 3 + Laravel + Vite**