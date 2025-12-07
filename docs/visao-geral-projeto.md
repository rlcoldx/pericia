# Visão Geral do Projeto - Sistema de Gestão de Perícias

## 1. Objetivo Principal

Desenvolver um **sistema integrado para gestão completa de perícias médicas/jurídicas**, incluindo:

- **Agendamento de perícias**
- **Controle financeiro** (contas a receber)
- **Gestão de documentos e pareceres**
- **Comunicação** com clientes e partes interessadas

## 2. Contexto do Negócio

O sistema será utilizado por escritórios ou profissionais que realizam perícias médicas/jurídicas, necessitando de uma solução completa para:

### 2.1 Gestão de Agendamentos
- Agendar perícias
- Controlar datas e horários
- Gerenciar disponibilidade de profissionais/peritos
- Notificações e lembretes

### 2.2 Controle Financeiro
- Controle de contas a receber
- Faturamento de perícias realizadas
- Relatórios financeiros
- Gestão de pagamentos e recebimentos

### 2.3 Gestão de Documentos
- Armazenamento de pareceres
- Documentos relacionados às perícias
- Versionamento de documentos
- Acesso controlado a documentos

### 2.4 Comunicação
- Comunicação com clientes
- Notificações para partes interessadas
- Histórico de comunicações
- Canal de comunicação integrado

## 3. Arquitetura e Tecnologias

### 3.1 Stack Tecnológico Atual
- **Backend**: PHP 7.4+
- **Framework**: Arquitetura MVC customizada
- **Banco de Dados**: MySQL (InnoDB)
- **Template Engine**: Twig 3.0
- **Roteamento**: CoffeeCode Router
- **HTTP Client**: Guzzle 7.0
- **Email**: PHPMailer 6.4

### 3.2 Estrutura do Projeto
```
application/
  ├── Controllers/      # Controladores MVC
  ├── Models/           # Modelos de dados
  ├── Migrations/       # Sistema de migrations
  ├── Services/         # Serviços (Login, Permissões)
  ├── Middleware/       # Middlewares de autenticação
  ├── Helpers/          # Classes auxiliares
  └── Conn/             # Conexão e operações de banco

view/
  ├── pages/            # Templates Twig das páginas
  ├── layout/           # Layouts base
  └── components/       # Componentes reutilizáveis

routes/                 # Definição de rotas
config/                 # Configurações do sistema
docs/                   # Documentação
```

## 4. Sistemas Já Implementados

### 4.1 Sistema de Autenticação e Usuários
- Login com sessão e cookies
- Gestão de usuários (tipo 1 = Admin, tipo 3 = Equipe)
- Sistema de recuperação de senha
- Perfis de usuário

### 4.2 Sistema de Permissões
- Tabela `permissoes` (nome, titulo, descricao, grupo)
- Tabela `usuario_permissoes` (relacionamento usuário-permissão)
- Tabela `cargo_permissoes` (relacionamento cargo-permissão)
- Permissões por cargo
- Administradores (tipo 1) têm acesso total sem verificação
- Middleware de verificação de permissões
- Padrão: `{sistema}_ver`, `{sistema}_criar`, `{sistema}_editar`, `{sistema}_deletar`

### 4.3 Sistema de Cargos e Equipe
- Gestão de cargos por empresa
- Associação de permissões a cargos
- Gestão de membros da equipe
- Vinculação de membros a cargos

### 4.4 Sistema de Migrations
- Gerenciamento de migrations do banco de dados
- Interface web para executar migrations
- Controle de versão do banco de dados
- Log de execuções e erros

## 5. Funcionalidades Principais a Desenvolver

### 5.1 Módulo de Agendamento de Perícias
- Cadastro de perícias
- Agendamento de datas/horários
- Associação de perito/profissional
- Associação de cliente
- Status da perícia (Pendente, Agendada, Realizada, Cancelada, etc.)
- Calendário de agendamentos
- Notificações e lembretes

### 5.2 Módulo Financeiro
- Contas a receber relacionadas a perícias
- Faturamento
- Controle de pagamentos
- Relatórios financeiros
- Dashboards com indicadores
- Histórico de transações

### 5.3 Módulo de Documentos
- Upload de documentos/pareceres
- Vinculação de documentos a perícias
- Versionamento
- Organização por categorias
- Controle de acesso por permissões
- Download e visualização

### 5.4 Módulo de Comunicação
- Mensagens relacionadas a perícias
- Notificações para clientes
- Histórico de comunicações
- Templates de mensagens
- Integração com email

### 5.5 Módulo de Clientes
- Cadastro de clientes
- Histórico de perícias do cliente
- Documentos do cliente
- Comunicações com o cliente
- Informações de contato e cadastro

### 5.6 Dashboard Principal
- Visão geral de perícias (agendadas, realizadas, pendentes)
- Indicadores financeiros
- Atividades recentes
- Gráficos e relatórios
- Acesso rápido a funcionalidades principais

## 6. Regras de Negócio Importantes

### 6.1 Permissões e Acesso
- **Administradores (tipo 1)**: Acesso total ao sistema sem verificação de permissões
- **Equipe (tipo 3)**: Acesso baseado em permissões do cargo
- **Permissões obrigatórias**: Todo novo sistema deve ter permissões `_ver`, `_criar`, `_editar`, `_deletar`

### 6.2 Migrations
- **OBRIGATÓRIO**: Toda alteração no banco de dados deve ter uma migration correspondente
- Migrations devem ser executadas através da interface web em `/migrations`
- Nunca fazer alterações diretas no banco sem criar migration

### 6.3 Multi-empresa
- Sistema parece ser multi-empresa (campo `empresa` em várias tabelas)
- Usuários vinculados a empresas
- Dados isolados por empresa

### 6.4 Tipos de Usuário
- Tipo 1: Administrador (acesso total)
- Tipo 2: Usuário (provavelmente cliente ou outro tipo)
- Tipo 3: Equipe (membros da equipe interna)

## 7. Estrutura de Dados Prevista

### 7.1 Tabelas Principais (a criar)
- **pericias**: Informações das perícias (data, horário, cliente, perito, status, etc.)
- **agendamentos**: Relacionamento perícia-agendamento (pode estar integrado em pericias)
- **clientes**: Cadastro de clientes
- **documentos**: Documentos e pareceres vinculados
- **financeiro/contas_receber**: Controle financeiro
- **comunicacoes**: Mensagens e notificações

### 7.2 Tabelas Existentes
- **usuarios**: Usuários do sistema
- **permissoes**: Permissões disponíveis
- **cargos**: Cargos da equipe
- **cargo_permissoes**: Permissões por cargo
- **usuario_permissoes**: Permissões diretas por usuário
- **migrations**: Controle de migrations

## 8. Fluxo de Trabalho Esperado

### 8.1 Fluxo de Uma Perícia
1. **Cadastro do Cliente** (se não existir)
2. **Criação da Perícia** com dados do cliente
3. **Agendamento** da data/horário
4. **Vinculação de Perito/Profissional**
5. **Realização da Perícia** (mudança de status)
6. **Upload de Parecer/Documentos**
7. **Geração de Fatura** (conta a receber)
8. **Envio de Documentos** para cliente
9. **Registro de Pagamento**

### 8.2 Comunicação
- Notificações automáticas em eventos importantes
- Comunicação manual com clientes
- Histórico de todas as interações

## 9. Interfaces e UX

### 9.1 Interface
- Layout responsivo
- Dashboard com visão geral
- Sidebar com menu por permissões
- Tabelas com DataTables para ordenação e busca
- Modais para confirmações e ações rápidas
- Formulários com validação

### 9.2 Componentes Reutilizáveis
- Cards de informações
- Tabelas de listagem
- Formulários padronizados
- Modais de confirmação
- Alertas e notificações

## 10. Considerações Técnicas

### 10.1 Segurança
- Senhas criptografadas (SHA1 - considerar migrar para bcrypt)
- Validação de permissões em todas as rotas
- Proteção contra SQL Injection (usando prepared statements)
- Validação de dados de entrada

### 10.2 Performance
- Índices em campos de busca frequente
- Queries otimizadas
- Lazy loading quando necessário

### 10.3 Manutenibilidade
- Código organizado em MVC
- Migrations para versionamento do banco
- Documentação de código
- Padrões consistentes

## 11. Próximos Passos Sugeridos

1. **Definir estrutura completa do banco de dados**
   - Modelar todas as tabelas necessárias
   - Criar migrations para todas as tabelas

2. **Criar permissões base**
   - Definir todas as permissões do sistema
   - Popular tabela `permissoes`

3. **Desenvolver módulo de Clientes**
   - CRUD completo
   - Validações
   - Permissões

4. **Desenvolver módulo de Perícias**
   - CRUD completo
   - Status workflow
   - Vinculações

5. **Desenvolver módulo de Agendamento**
   - Calendário
   - Disponibilidade
   - Conflitos

6. **Desenvolver módulo Financeiro**
   - Contas a receber
   - Faturamento
   - Relatórios

7. **Desenvolver módulo de Documentos**
   - Upload
   - Armazenamento
   - Vinculação

8. **Dashboard principal**
   - Indicadores
   - Gráficos
   - Acesso rápido

---

**Nota**: Este documento foi criado com base na visão geral fornecida e análise do código existente. Pode ser atualizado conforme o projeto evolui.

