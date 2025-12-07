<?php

namespace Agencia\Close\Services\Notificacao;

use Agencia\Close\Adapters\EmailAdapter;
use Agencia\Close\Models\EmailTemplate\EmailTemplate;
use Agencia\Close\Models\User\UserPermissions;
use Agencia\Close\Models\Notificacao\NotificacaoUsuario;
use Exception;

class EmailNotificationService
{
    protected EmailTemplate $templateModel;
    protected UserPermissions $userPermissionsModel;
    protected NotificacaoUsuario $notificacaoUsuarioModel;

    public function __construct()
    {
        $this->templateModel = new EmailTemplate();
        $this->userPermissionsModel = new UserPermissions();
        $this->notificacaoUsuarioModel = new NotificacaoUsuario();
    }

    /**
     * Envia notificação por e-mail para usuários com permissões específicas
     * 
     * @param string $tipo Tipo da notificação (ex: 'quesito_criar', 'quesito_editar')
     * @param array $permissoes Array de permissões necessárias para receber o e-mail
     * @param array $dados Dados para substituir no template
     * @param int $empresa ID da empresa
     * @param bool $enviarParaAdmin Se true, também envia para administradores
     * @return array Resultado do envio
     */
    public function enviarNotificacao(
        string $tipo,
        array $permissoes,
        array $dados,
        int $empresa,
        bool $enviarParaAdmin = true
    ): array {
        $resultado = [
            'sucesso' => 0,
            'erros' => 0,
            'destinatarios' => []
        ];

        try {
            // Buscar template de e-mail
            $templateRead = $this->templateModel->getPorTipo($tipo, $empresa);
            $template = $templateRead->getResult()[0] ?? null;

            if (!$template || !$template['ativo']) {
                return $resultado; // Template não encontrado ou inativo
            }

            // Buscar destinatários
            $destinatarios = [];

            // Usuários com permissões específicas
            if (!empty($permissoes)) {
                $usuariosRead = $this->userPermissionsModel->getUsuariosPorPermissoes($permissoes, $empresa);
                $usuarios = $usuariosRead->getResult() ?? [];
                $destinatarios = array_merge($destinatarios, $usuarios);
            }

            // Administradores
            if ($enviarParaAdmin) {
                $adminRead = $this->userPermissionsModel->getAdministradores($empresa);
                $admins = $adminRead->getResult() ?? [];
                $destinatarios = array_merge($destinatarios, $admins);
            }

            // Remover duplicatas
            $destinatarios = array_values(array_unique(array_column($destinatarios, 'id')));

            if (empty($destinatarios)) {
                return $resultado;
            }

            // Preparar corpo do e-mail
            $assunto = $this->substituirVariaveis($template['assunto'], $dados);
            $corpo = $this->substituirVariaveis($template['corpo'], $dados);

            // Enviar e-mail para cada destinatário
            foreach ($destinatarios as $destinatario) {
                if (empty($destinatario['email'])) {
                    continue;
                }

                try {
                    $emailAdapter = new EmailAdapter();
                    $emailAdapter->addAddress($destinatario['email']);
                    $emailAdapter->setSubject($assunto);
                    $emailAdapter->setBodyHtml($corpo); // Usar corpo direto
                    
                    // Enviar e-mail
                    $emailAdapter->send('E-mail enviado com sucesso');
                    $result = $emailAdapter->getResult();

                    if (!$result->getError()) {
                        $resultado['sucesso']++;
                        $resultado['destinatarios'][] = [
                            'id' => $destinatario['id'],
                            'email' => $destinatario['email'],
                            'status' => 'enviado'
                        ];
                        
                        // Marcar e-mail como enviado na notificação (se houver tipo/modulo/acao)
                        // Isso será feito no método criarNotificacaoEEmail
                    } else {
                        $resultado['erros']++;
                        $resultado['destinatarios'][] = [
                            'id' => $destinatario['id'],
                            'email' => $destinatario['email'],
                            'status' => 'erro',
                            'mensagem' => $result->getMessage()
                        ];
                    }
                } catch (Exception $e) {
                    $resultado['erros']++;
                    $resultado['destinatarios'][] = [
                        'id' => $destinatario['id'],
                        'email' => $destinatario['email'],
                        'status' => 'erro',
                        'mensagem' => $e->getMessage()
                    ];
                }
            }

        } catch (Exception $e) {
            $resultado['erro_geral'] = $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Substitui variáveis no template
     * 
     * @param string $texto Texto com variáveis
     * @param array $dados Dados para substituir
     * @return string Texto com variáveis substituídas
     */
    protected function substituirVariaveis(string $texto, array $dados): string
    {
        foreach ($dados as $chave => $valor) {
            $texto = str_replace('{{' . $chave . '}}', $valor, $texto);
        }
        return $texto;
    }

    /**
     * Cria notificação no banco e envia e-mail
     * 
     * @param string $tipo Tipo da notificação
     * @param string $modulo Módulo (ex: 'quesito', 'parecer')
     * @param string $acao Ação (ex: 'criar', 'editar')
     * @param array $permissoes Permissões necessárias
     * @param array $dados Dados para o template
     * @param int $empresa ID da empresa
     * @param int|null $registroId ID do registro relacionado
     * @param bool $enviarParaAdmin Se true, também envia para administradores
     */
    public function criarNotificacaoEEmail(
        string $tipo,
        string $modulo,
        string $acao,
        array $permissoes,
        array $dados,
        int $empresa,
        ?int $registroId = null,
        bool $enviarParaAdmin = true
    ): void {
        // Buscar destinatários
        $destinatarios = [];

        if (!empty($permissoes)) {
            $usuariosRead = $this->userPermissionsModel->getUsuariosPorPermissoes($permissoes, $empresa);
            $usuarios = $usuariosRead->getResult() ?? [];
            $destinatarios = array_merge($destinatarios, $usuarios);
        }

        if ($enviarParaAdmin) {
            $adminRead = $this->userPermissionsModel->getAdministradores($empresa);
            $admins = $adminRead->getResult() ?? [];
            $destinatarios = array_merge($destinatarios, $admins);
        }

        // Remover duplicatas
        $destinatariosIds = array_unique(array_column($destinatarios, 'id'));

        // Criar notificação para cada destinatário
        $titulo = $dados['titulo'] ?? ucfirst($modulo) . ' ' . ucfirst($acao);
        $mensagem = $dados['mensagem'] ?? 'Uma nova ação foi realizada no sistema.';
        $url = $dados['url'] ?? null;

        foreach ($destinatariosIds as $usuarioId) {
            $this->notificacaoUsuarioModel->criarParaUsuario(
                (int)$usuarioId,
                $titulo,
                $mensagem,
                $url,
                $tipo,
                $modulo,
                $acao,
                $registroId
            );
        }

        // Enviar e-mails
        $resultadoEnvio = $this->enviarNotificacao($tipo, $permissoes, $dados, $empresa, $enviarParaAdmin);
        
        // Marcar e-mails como enviados para os destinatários que receberam com sucesso
        if ($resultadoEnvio['sucesso'] > 0) {
            foreach ($destinatariosIds as $usuarioId) {
                // Verifica se o e-mail foi enviado com sucesso para este usuário
                $enviadoComSucesso = false;
                foreach ($resultadoEnvio['destinatarios'] ?? [] as $dest) {
                    if ($dest['id'] == $usuarioId && $dest['status'] === 'enviado') {
                        $enviadoComSucesso = true;
                        break;
                    }
                }
                
                if ($enviadoComSucesso) {
                    $this->notificacaoUsuarioModel->marcarEmailEnviado(
                        (int)$usuarioId,
                        $tipo,
                        $modulo,
                        $acao,
                        $registroId
                    );
                }
            }
        }
    }

    /**
     * Busca um template de e-mail por tipo
     * 
     * @param string $tipo Tipo do template
     * @param int $empresa ID da empresa
     * @return array|null Template encontrado ou null
     */
    public function getTemplate(string $tipo, int $empresa): ?array
    {
        try {
            $templateRead = $this->templateModel->getPorTipo($tipo, $empresa);
            return $templateRead->getResult()[0] ?? null;
        } catch (Exception $e) {
            error_log('Erro ao buscar template: ' . $e->getMessage());
            return null;
        }
    }
}
