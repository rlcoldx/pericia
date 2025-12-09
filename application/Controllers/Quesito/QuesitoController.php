<?php

namespace Agencia\Close\Controllers\Quesito;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Quesito\Quesito;
use Agencia\Close\Models\Reclamada\Reclamada;
use Agencia\Close\Models\Reclamante\Reclamante;
use Agencia\Close\Models\Notificacao\NotificacaoUsuario;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Conn\Read;
use Agencia\Close\Helpers\DataTableResponse;

class QuesitoController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('quesito_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $filtros = [];
        if (!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        if (!empty($_GET['tipo'])) {
            $filtros['tipo'] = trim($_GET['tipo']);
        }
        if (!empty($_GET['vara'])) {
            $filtros['vara'] = trim($_GET['vara']);
        }
        if (!empty($_GET['reclamante'])) {
            $filtros['reclamante'] = trim($_GET['reclamante']);
        }
        if (!empty($_GET['reclamada'])) {
            $filtros['reclamada'] = trim($_GET['reclamada']);
        }

        $this->render('pages/quesito/index.twig', [
            'titulo' => 'Quesitos',
            'page' => 'quesitos',
            'filtros' => $filtros,
        ]);
    }

    /**
     * Endpoint AJAX para DataTables de Quesitos
     */
    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('quesito_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson([
                'draw' => (int) ($_GET['draw'] ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
            return;
        }

        $dtParams = DataTableResponse::getParams();

        $filtros = [];
        if (!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        if (!empty($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }
        if (!empty($_GET['vara'])) {
            $filtros['vara'] = $_GET['vara'];
        }
        if (!empty($_GET['reclamante'])) {
            $filtros['reclamante'] = $_GET['reclamante'];
        }
        if (!empty($_GET['reclamada'])) {
            $filtros['reclamada'] = $_GET['reclamada'];
        }

        $model = new Quesito();
        $result = $model->getQuesitosDataTable((int) $empresa, $dtParams, $filtros);

        $formattedData = [];
        foreach ($result['data'] as $q) {
            $formattedData[] = [
                date('d/m/Y', strtotime($q['data'])),
                htmlspecialchars($q['tipo'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($q['vara'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($q['reclamante_nome'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($q['reclamada_nome'] ?? '', ENT_QUOTES, 'UTF-8'),
                $this->formatStatusBadge($q['status'] ?? 'Pendente'),
                $this->formatEmailClienteCell($q),
                $this->formatAcoesCell($q['id'] ?? null),
            ];
        }

        $response = DataTableResponse::format(
            $formattedData,
            (int) $result['total'],
            (int) $result['filtered'],
            (int) $dtParams['draw']
        );

        $this->responseJson($response);
    }

    private function formatStatusBadge(string $status): string
    {
        $badgeClass = 'bg-secondary';

        switch ($status) {
            case 'Pendente':
                $badgeClass = 'bg-warning';
                break;
            case 'Finalizado':
                $badgeClass = 'bg-success';
                break;
            case 'Pendente de Envio':
                $badgeClass = 'bg-info';
                break;
            case 'Recusado':
                $badgeClass = 'bg-danger';
                break;
        }

        return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    private function formatEmailClienteCell(array $q): string
    {
        $emailCliente = isset($q['email_cliente']) ? trim($q['email_cliente']) : '';
        
        // Se não tem email do cliente, mostra badge cinza
        if (empty($emailCliente)) {
            return '<span class="badge bg-secondary" data-bs-toggle="tooltip" title="E-mail do cliente não informado">
                        <i class="fa fa-minus"></i>
                    </span>';
        }

        // Verifica se o email foi enviado
        $emailEnviado = false;
        if (isset($q['email_cliente_enviado'])) {
            $valor = $q['email_cliente_enviado'];
            $emailEnviado = ($valor === 1 || $valor === '1' || (int)$valor === 1);
        }
        
        $dataEnvio = $q['email_cliente_data_envio'] ?? null;

        // Se o email foi enviado
        if ($emailEnviado) {
            if (!empty($dataEnvio) && $dataEnvio !== '0000-00-00 00:00:00') {
                try {
                    $dataFormatada = date('d/m/Y H:i', strtotime($dataEnvio));
                    return '<span class="badge bg-success" data-bs-toggle="tooltip" title="E-mail enviado em ' . htmlspecialchars($dataFormatada, ENT_QUOTES, 'UTF-8') . '">
                                <i class="fa fa-check-circle me-1"></i>Enviado
                            </span>';
                } catch (\Exception $e) {
                    // Se houver erro ao formatar data, mostra apenas "Enviado"
                    return '<span class="badge bg-success" data-bs-toggle="tooltip" title="E-mail enviado">
                                <i class="fa fa-check-circle me-1"></i>Enviado
                            </span>';
                }
            } else {
                // Enviado mas sem data
                return '<span class="badge bg-success" data-bs-toggle="tooltip" title="E-mail enviado">
                            <i class="fa fa-check-circle me-1"></i>Enviado
                        </span>';
            }
        }

        // Caso contrário, está pendente
        return '<span class="badge bg-warning" data-bs-toggle="tooltip" title="E-mail pendente de envio">
                    <i class="fa fa-clock me-1"></i>Pendente
                </span>';
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('quesito_cadastrar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $quesitoModel = new Quesito();

        $this->render('pages/quesito/form.twig', [
            'titulo' => 'Cadastrar Quesito',
            'page' => 'quesitos',
            'action' => 'criar',
            'quesito' => null,
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'tipos' => $quesitoModel->getTiposDistinct((int) $empresa)->getResult() ?? [],
        ]);
    }

    public function salvarCriar($params)
    {
        $this->setParams($params);
        $this->requirePermission('quesito_cadastrar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $usuarioId = $_SESSION['pericia_perfil_id'] ?? null;

        if (!$empresa || !$usuarioId) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
            return;
        }

        $data = $_POST['data'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $vara = $_POST['vara'] ?? '';
        $reclamanteId = !empty($_POST['reclamante_id']) ? (int) $_POST['reclamante_id'] : null;
        $reclamadaId = !empty($_POST['reclamada_id']) ? (int) $_POST['reclamada_id'] : null;
        $emailCliente = $_POST['email_cliente'] ?? '';
        
        // Processar emails CC (vem como string do Bootstrap Tags Input ou array)
        $emailsCc = $_POST['email_cliente_cc'] ?? '';
        $emailClienteCc = null;
        
        // Se for string (Bootstrap Tags Input), converter para array
        if (is_string($emailsCc) && !empty($emailsCc)) {
            $emailsCc = array_filter(array_map('trim', explode(',', $emailsCc)));
        }
        
        if (is_array($emailsCc) && !empty($emailsCc)) {
            // Filtrar emails vazios e validar
            $emailsValidos = array_filter(array_map('trim', $emailsCc), function($email) {
                return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            if (!empty($emailsValidos)) {
                $emailClienteCc = implode(',', $emailsValidos);
            }
        }

        // Força o tipo em letras maiúsculas
        if (!empty($tipo)) {
            $tipo = mb_strtoupper($tipo, 'UTF-8');
        }

        if (empty($data) || empty($tipo) || empty($vara) || !$reclamanteId || !$reclamadaId) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            return;
        }

        $dados = [
            'empresa' => (int) $empresa,
            'data' => $data,
            'tipo' => $tipo,
            'vara' => $vara,
            'reclamante_id' => $reclamanteId,
            'codigo_reclamante' => $_POST['codigo_reclamante'] !== '' ? $_POST['codigo_reclamante'] : null,
            'email_cliente' => $emailCliente !== '' ? $emailCliente : null,
            'email_cliente_cc' => $emailClienteCc,
            'reclamada_id' => $reclamadaId,
            'link_pasta_drive' => $_POST['link_pasta_drive'] !== '' ? $_POST['link_pasta_drive'] : null,
            'enviar_para_cliente' => isset($_POST['enviar_para_cliente']) ? 1 : 0,
            'status' => 'Pendente',
        ];

        $model = new Quesito();
        $result = $model->criar($dados);

        if (!$result->getResult()) {
            $this->responseJson(['success' => false, 'message' => 'Erro ao cadastrar o quesito.']);
            return;
        }

        $idQuesito = (int) $result->getResult();

        $this->notificarResponsaveisQuesito($empresa, $idQuesito, $dados);
        
        // Enviar notificação por e-mail
        $this->enviarNotificacaoEmailQuesito($empresa, $idQuesito, $dados, 'criar');

        $this->responseJson([
            'success' => true,
            'message' => 'Quesito cadastrado com sucesso.',
        ]);
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('quesito_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$empresa || !$id) {
            $this->redirectUrl(DOMAIN . '/quesitos');
            return;
        }

        $model = new Quesito();
        $quesito = $model->getPorId($id, (int) $empresa);

        if (!$quesito->getResult()) {
            $this->redirectUrl(DOMAIN . '/quesitos');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();

        $this->render('pages/quesito/form.twig', [
            'titulo' => 'Gerenciar Quesito',
            'page' => 'quesitos',
            'action' => 'editar',
            'quesito' => $quesito->getResult()[0],
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'tipos' => $model->getTiposDistinct((int) $empresa)->getResult() ?? [],
        ]);
    }

    public function salvarEditar($params)
    {
        $this->setParams($params);
        $this->requirePermission('quesito_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

        if (!$empresa || !$id) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $data = $_POST['data'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $vara = $_POST['vara'] ?? '';
        $reclamanteId = !empty($_POST['reclamante_id']) ? (int) $_POST['reclamante_id'] : null;
        $reclamadaId = !empty($_POST['reclamada_id']) ? (int) $_POST['reclamada_id'] : null;
        $emailCliente = $_POST['email_cliente'] ?? '';
        $status = $_POST['status'] ?? 'Pendente';
        
        // Processar emails CC (vem como string do Bootstrap Tags Input ou array)
        $emailsCc = $_POST['email_cliente_cc'] ?? '';
        $emailClienteCc = null;
        
        // Se for string (Bootstrap Tags Input), converter para array
        if (is_string($emailsCc) && !empty($emailsCc)) {
            $emailsCc = array_filter(array_map('trim', explode(',', $emailsCc)));
        }
        
        if (is_array($emailsCc) && !empty($emailsCc)) {
            // Filtrar emails vazios e validar
            $emailsValidos = array_filter(array_map('trim', $emailsCc), function($email) {
                return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            if (!empty($emailsValidos)) {
                $emailClienteCc = implode(',', $emailsValidos);
            }
        }

        $statusValidos = ['Pendente', 'Finalizado', 'Pendente de Envio', 'Recusado'];
        if (!in_array($status, $statusValidos, true)) {
            $status = 'Pendente';
        }

        // Força o tipo em letras maiúsculas
        if (!empty($tipo)) {
            $tipo = mb_strtoupper($tipo, 'UTF-8');
        }

        if (empty($data) || empty($tipo) || empty($vara) || !$reclamanteId || !$reclamadaId) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            return;
        }

        $dados = [
            'data' => $data,
            'tipo' => $tipo,
            'vara' => $vara,
            'reclamante_id' => $reclamanteId,
            'codigo_reclamante' => $_POST['codigo_reclamante'] !== '' ? $_POST['codigo_reclamante'] : null,
            'email_cliente' => $emailCliente !== '' ? $emailCliente : null,
            'email_cliente_cc' => $emailClienteCc,
            'reclamada_id' => $reclamadaId,
            'link_pasta_drive' => $_POST['link_pasta_drive'] !== '' ? $_POST['link_pasta_drive'] : null,
            'enviar_para_cliente' => isset($_POST['enviar_para_cliente']) ? 1 : 0,
            'status' => $status,
        ];

        $model = new Quesito();
        $result = $model->atualizar($id, (int) $empresa, $dados);

        if (!$result->getResult()) {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar o quesito.']);
            return;
        }

        // Enviar notificação por e-mail
        $this->enviarNotificacaoEmailQuesito($empresa, $id, $dados, 'editar');

        // Verificar se deve enviar email ao cliente
        if (isset($_POST['enviar_para_cliente']) && $_POST['enviar_para_cliente'] == '1' && !empty($emailCliente)) {
            // Buscar emails CC do quesito atualizado
            $quesitoAtualizado = $model->getPorId($id, (int) $empresa);
            $quesitoData = $quesitoAtualizado->getResult()[0] ?? [];
            $emailsCcArray = [];
            if (!empty($quesitoData['email_cliente_cc'])) {
                $emailsCcArray = array_filter(array_map('trim', explode(',', $quesitoData['email_cliente_cc'])));
            }
            
            $emailEnviado = $this->enviarEmailClienteQuesito($empresa, $id, $dados, $emailCliente, $emailsCcArray);
            
            if ($emailEnviado) {
                // Atualizar status de envio
                $model->atualizar($id, (int) $empresa, [
                    'email_cliente_enviado' => 1,
                    'email_cliente_data_envio' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $this->responseJson([
            'success' => true,
            'message' => 'Quesito atualizado com sucesso.',
        ]);
    }

    private function notificarResponsaveisQuesito(int $empresa, int $idQuesito, array $dadosQuesito): void
    {
        $permissaoNome = 'quesito_gerenciar';

        $read = new Read();

        $sql = "SELECT u.id 
                FROM usuarios u
                INNER JOIN cargos c ON c.id = u.cargo_id AND c.empresa = :empresa
                INNER JOIN cargo_permissoes cp ON cp.cargo_id = c.id
                INNER JOIN permissoes p ON p.id = cp.permissao_id
                WHERE p.nome = :permissao";

        $read->FullRead($sql, "empresa={$empresa}&permissao={$permissaoNome}");

        $usuarios = $read->getResult() ?? [];

        if (empty($usuarios)) {
            return;
        }

        $ids = array_map(static function ($row) {
            return (int) $row['id'];
        }, $usuarios);

        // Buscar nome do reclamante
        $reclamanteModel = new Reclamante();
        $reclamanteRead = $reclamanteModel->getPorId($dadosQuesito['reclamante_id'], $empresa);
        $reclamanteNome = $reclamanteRead->getResult()[0]['nome'] ?? 'N/A';

        $titulo = 'Novo Quesito Cadastrado';
        $mensagem = sprintf(
            'Quesito para o reclamante %s na vara %s foi cadastrado.',
            $reclamanteNome,
            $dadosQuesito['vara']
        );
        $url = DOMAIN . '/quesitos/editar/' . $idQuesito;

        $notificacaoModel = new NotificacaoUsuario();
        $notificacaoModel->criarParaUsuarios($ids, $titulo, $mensagem, $url);
    }

    private function enviarNotificacaoEmailQuesito(int $empresa, int $idQuesito, array $dadosQuesito, string $acao): void
    {
        try {
            $reclamanteModel = new Reclamante();
            $reclamadaModel = new Reclamada();
            
            $reclamanteRead = $reclamanteModel->getPorId($dadosQuesito['reclamante_id'], $empresa);
            $reclamadaRead = $reclamadaModel->getPorId($dadosQuesito['reclamada_id'], $empresa);
            
            $reclamanteNome = $reclamanteRead->getResult()[0]['nome'] ?? 'N/A';
            $reclamadaNome = $reclamadaRead->getResult()[0]['nome'] ?? 'N/A';

            $dados = [
                'titulo' => $acao === 'criar' ? 'Novo Quesito Cadastrado' : 'Quesito Atualizado',
                'modulo' => 'Quesito',
                'acao' => $acao === 'criar' ? 'Criado' : 'Editado',
                'detalhes' => sprintf(
                    '<p><strong>Data:</strong> %s</p><p><strong>Tipo:</strong> %s</p><p><strong>Vara:</strong> %s</p><p><strong>Reclamante:</strong> %s</p><p><strong>Reclamada:</strong> %s</p><p><strong>Status:</strong> %s</p>',
                    date('d/m/Y', strtotime($dadosQuesito['data'])),
                    htmlspecialchars($dadosQuesito['tipo']),
                    htmlspecialchars($dadosQuesito['vara']),
                    htmlspecialchars($reclamanteNome),
                    htmlspecialchars($reclamadaNome),
                    htmlspecialchars($dadosQuesito['status'] ?? 'Pendente')
                ),
                'mensagem' => sprintf(
                    'Quesito para o reclamante %s na vara %s foi %s.',
                    $reclamanteNome,
                    $dadosQuesito['vara'],
                    $acao === 'criar' ? 'cadastrado' : 'atualizado'
                ),
                'url' => DOMAIN . '/quesitos/editar/' . $idQuesito
            ];

            $emailService = new EmailNotificationService();
            $tipo = $acao === 'criar' ? 'quesito_criar' : 'quesito_editar';
            $permissoes = ['quesito_gerenciar', 'quesito_ver'];
            
            $emailService->criarNotificacaoEEmail(
                $tipo,
                'quesito',
                $acao,
                $permissoes,
                $dados,
                $empresa,
                $idQuesito,
                true // Enviar para admin também
            );
        } catch (\Exception $e) {
            // Log do erro, mas não interrompe o fluxo
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
        }
    }

    private function enviarEmailClienteQuesito(int $empresa, int $idQuesito, array $dadosQuesito, string $emailCliente, array $emailsCc = []): bool
    {
        try {
            $reclamanteModel = new Reclamante();
            $reclamadaModel = new Reclamada();
            
            $reclamanteRead = $reclamanteModel->getPorId($dadosQuesito['reclamante_id'], $empresa);
            $reclamadaRead = $reclamadaModel->getPorId($dadosQuesito['reclamada_id'], $empresa);
            
            $reclamanteNome = $reclamanteRead->getResult()[0]['nome'] ?? 'N/A';
            $reclamadaNome = $reclamadaRead->getResult()[0]['nome'] ?? 'N/A';

            $dados = [
                'titulo' => 'Quesito - ' . htmlspecialchars($dadosQuesito['tipo']),
                'modulo' => 'Quesito',
                'acao' => 'Enviado',
                'detalhes' => sprintf(
                    '<p><strong>Data:</strong> %s</p><p><strong>Tipo:</strong> %s</p><p><strong>Vara:</strong> %s</p><p><strong>Reclamante:</strong> %s</p><p><strong>Reclamada:</strong> %s</p><p><strong>Status:</strong> %s</p>',
                    date('d/m/Y', strtotime($dadosQuesito['data'])),
                    htmlspecialchars($dadosQuesito['tipo']),
                    htmlspecialchars($dadosQuesito['vara']),
                    htmlspecialchars($reclamanteNome),
                    htmlspecialchars($reclamadaNome),
                    htmlspecialchars($dadosQuesito['status'] ?? 'Pendente')
                ),
                'mensagem' => sprintf(
                    'Informamos que o quesito para o reclamante %s na vara %s foi atualizado.',
                    $reclamanteNome,
                    $dadosQuesito['vara']
                ),
                'url' => '' // Cliente não tem acesso ao sistema
            ];

            $emailService = new EmailNotificationService();
            
            // Enviar email diretamente para o cliente (não usa permissões)
            $template = $emailService->getTemplate('quesito_enviar_cliente', $empresa);
            
            if (!$template || !$template['ativo']) {
                error_log('Template quesito_enviar_cliente não encontrado ou inativo');
                return false;
            }

            // Substituir variáveis no template
            $assunto = $template['assunto'];
            $corpo = $template['corpo'];
            
            foreach ($dados as $key => $value) {
                $assunto = str_replace('{{' . $key . '}}', $value, $assunto);
                $corpo = str_replace('{{' . $key . '}}', $value, $corpo);
            }

            // Enviar email usando EmailAdapter
            $emailAdapter = new \Agencia\Close\Adapters\EmailAdapter();
            $emailAdapter->addAddress($emailCliente);
            
            // Adicionar emails CC se houver
            if (!empty($emailsCc)) {
                $emailAdapter->addMultipleCC($emailsCc);
            }
            
            $emailAdapter->setSubject($assunto);
            $emailAdapter->setBodyHtml($corpo);
            
            try {
                $emailAdapter->send('Email enviado com sucesso');
                $result = $emailAdapter->getResult();
                
                if (!$result->getError()) {
                    $ccInfo = !empty($emailsCc) ? ' (CC: ' . implode(', ', $emailsCc) . ')' : '';
                    error_log("Email enviado com sucesso para o cliente: {$emailCliente}{$ccInfo}");
                    return true;
                } else {
                    error_log("Erro ao enviar email para o cliente: {$emailCliente} - " . $result->getMessage());
                    return false;
                }
            } catch (\Exception $e) {
                error_log("Exceção ao enviar email para cliente: " . $e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            error_log('Erro ao enviar email para cliente: ' . $e->getMessage());
            return false;
        }
    }

    private function formatAcoesCell(?int $id): string
    {
        if (!$id) {
            return '';
        }

        $html = '<div class="d-flex justify-content-end">';

        // Botão Gerenciar (editar)
        $html .= '<a href="' . DOMAIN . '/quesitos/editar/' . $id . '" ';
        $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
        $html .= 'data-bs-toggle="tooltip" data-bs-title="Gerenciar">';
        $html .= '<i class="fa fa-pencil"></i></a>';

        $html .= '</div>';
        return $html;
    }
}

