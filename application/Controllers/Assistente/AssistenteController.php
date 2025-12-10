<?php

namespace Agencia\Close\Controllers\Assistente;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Assistente\Assistente;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AssistenteController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new Assistente();
        $lista = $model->listar((int) $empresa);

        $this->render('pages/assistente/index.twig', [
            'titulo' => 'Assistentes',
            'page' => 'assistentes',
            'assistentes' => $lista->getResult() ?? [],
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new Assistente();

        $this->render('pages/assistente/form.twig', [
            'titulo' => 'Novo Assistente',
            'page' => 'assistentes',
            'action' => 'criar',
            'assistente' => null,
            'credenciais' => $model->getCredenciaisDistinct((int) $empresa)->getResult() ?? [],
        ]);
    }

    public function salvarCriar($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }

        // Forçar credencial em maiúsculas
        $credencial = $_POST['credencial'] ?? null;
        if (!empty($credencial)) {
            $credencial = mb_strtoupper($credencial, 'UTF-8');
        }

        $model = new Assistente();
        $result = $model->criar([
            'empresa' => (int) $empresa,
            'nome' => $nome,
            'nome_contato' => !empty($_POST['nome_contato']) ? trim($_POST['nome_contato']) : null,
            'email_contato' => !empty($_POST['email_contato']) ? trim($_POST['email_contato']) : null,
            'telefone_contato' => !empty($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : null,
            'profissao' => !empty($_POST['profissao']) ? trim($_POST['profissao']) : null,
            'credencial' => $credencial,
            'numero_credencial' => !empty($_POST['numero_credencial']) ? trim($_POST['numero_credencial']) : null,
            'cidade_estado' => !empty($_POST['cidade_estado']) ? trim($_POST['cidade_estado']) : null,
        ]);

        if ($result->getResult()) {
            $idAssistente = (int) $result->getResult();
            $this->enviarNotificacaoEmailAssistente($empresa, $idAssistente, ['nome' => $nome], 'criar');
            $this->responseJson(['success' => true, 'message' => 'Assistente cadastrado com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao cadastrar assistente.']);
        }
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$empresa || !$id) {
            $this->redirectUrl(DOMAIN . '/assistentes');
            return;
        }

        $model = new Assistente();
        $assistente = $model->getPorId($id, (int) $empresa);

        if (!$assistente->getResult()) {
            $this->redirectUrl(DOMAIN . '/assistentes');
            return;
        }

        $this->render('pages/assistente/form.twig', [
            'titulo' => 'Editar Assistente',
            'page' => 'assistentes',
            'action' => 'editar',
            'assistente' => $assistente->getResult()[0],
            'credenciais' => $model->getCredenciaisDistinct((int) $empresa)->getResult() ?? [],
        ]);
    }

    public function salvarEditar($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $nome = trim($_POST['nome'] ?? '');

        if (!$empresa || !$id || empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        // Forçar credencial em maiúsculas
        $credencial = $_POST['credencial'] ?? null;
        if (!empty($credencial)) {
            $credencial = mb_strtoupper($credencial, 'UTF-8');
        }

        $model = new Assistente();
        $result = $model->atualizar($id, (int) $empresa, [
            'nome' => $nome,
            'nome_contato' => !empty($_POST['nome_contato']) ? trim($_POST['nome_contato']) : null,
            'email_contato' => !empty($_POST['email_contato']) ? trim($_POST['email_contato']) : null,
            'telefone_contato' => !empty($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : null,
            'profissao' => !empty($_POST['profissao']) ? trim($_POST['profissao']) : null,
            'credencial' => $credencial,
            'numero_credencial' => !empty($_POST['numero_credencial']) ? trim($_POST['numero_credencial']) : null,
            'cidade_estado' => !empty($_POST['cidade_estado']) ? trim($_POST['cidade_estado']) : null,
        ]);

        if ($result->getResult()) {
            $this->enviarNotificacaoEmailAssistente($empresa, $id, ['nome' => $nome], 'editar');
            $this->responseJson(['success' => true, 'message' => 'Assistente atualizado com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar assistente.']);
        }
    }

    private function enviarNotificacaoEmailAssistente(int $empresa, int $idAssistente, array $dados, string $acao): void
    {
        try {
            $dadosEmail = [
                'titulo' => $acao === 'criar' ? 'Novo Assistente Cadastrado' : 'Assistente Atualizado',
                'modulo' => 'Assistente',
                'acao' => $acao === 'criar' ? 'Criado' : 'Editado',
                'detalhes' => sprintf('<p><strong>Nome:</strong> %s</p>', htmlspecialchars($dados['nome'] ?? 'N/A')),
                'mensagem' => sprintf('Assistente %s foi %s.', htmlspecialchars($dados['nome'] ?? ''), $acao === 'criar' ? 'cadastrado' : 'atualizado'),
                'url' => DOMAIN . '/assistentes'
            ];

            $emailService = new EmailNotificationService();
            $tipo = $acao === 'criar' ? 'assistente_criar' : 'assistente_editar';
            $permissoes = ['assistente_ver', 'assistente_editar'];
            
            $emailService->criarNotificacaoEEmail($tipo, 'assistente', $acao, $permissoes, $dadosEmail, $empresa, $idAssistente, true);
        } catch (\Exception $e) {
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
        }
    }

    public function remover($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_deletar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

        if (!$empresa || !$id) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $model = new Assistente();
        $result = $model->remover($id, (int) $empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Assistente removido com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover assistente.']);
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_ver');

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

        $model = new Assistente();
        $result = $model->getAssistentesDataTable((int) $empresa, $dtParams, $filtros);

        $formattedData = [];
        foreach ($result['data'] as $a) {
            $formattedData[] = [
                htmlspecialchars($a['nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($a['profissao'] ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($a['cidade_estado'] ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($a['telefone_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                $this->formatAcoesCell($a['id'] ?? null),
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

    private function formatAcoesCell(?int $id): string
    {
        if (!$id) {
            return '';
        }

        $permissionService = new \Agencia\Close\Services\Login\PermissionsService();

        $html = '<div class="d-flex">';
        if ($permissionService->verifyPermissions('assistentes_editar')) {
            $html .= '<a href="' . DOMAIN . '/assistentes/editar/' . $id . '" ';
            $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
            $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
            $html .= '<i class="fa fa-pencil"></i></a>';
        }
        if ($permissionService->verifyPermissions('assistentes_deletar')) {
            $html .= '<button type="button" class="btn btn-danger shadow btn-xs sharp" ';
            $html .= 'onclick="removerAssistente(' . $id . ', \'' . htmlspecialchars('Assistente', ENT_QUOTES) . '\')" ';
            $html .= 'data-bs-toggle="tooltip" data-bs-title="Remover">';
            $html .= '<i class="fa fa-trash"></i></button>';
        }
        $html .= '</div>';
        return $html;
    }

    public function importar($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $this->render('pages/assistente/importar.twig', [
            'titulo' => 'Importar Assistentes',
            'page' => 'assistentes',
        ]);
    }

    public function processarImportacao($params)
    {
        $this->setParams($params);
        $this->requirePermission('assistentes_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            $this->responseJson(['success' => false, 'message' => 'Erro ao fazer upload do arquivo.']);
            return;
        }

        $arquivo = $_FILES['arquivo'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, ['xlsx', 'xls', 'csv'])) {
            $this->responseJson(['success' => false, 'message' => 'Formato de arquivo inválido. Use .xlsx, .xls ou .csv']);
            return;
        }

        try {
            // Tratamento especial para CSV
            if ($extensao === 'csv') {
                $rows = [];
                if (($handle = fopen($arquivo['tmp_name'], 'r')) !== false) {
                    // Detecta o delimitador
                    $delimiter = ',';
                    $firstLine = fgets($handle);
                    rewind($handle);
                    
                    if (strpos($firstLine, ';') !== false) {
                        $delimiter = ';';
                    }
                    
                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        $rows[] = $data;
                    }
                    fclose($handle);
                }
            } else {
                $spreadsheet = IOFactory::load($arquivo['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
            }

            if (empty($rows) || count($rows) < 2) {
                $this->responseJson(['success' => false, 'message' => 'O arquivo está vazio ou não possui dados.']);
                return;
            }

            // Remove o cabeçalho (primeira linha)
            array_shift($rows);

            $model = new Assistente();
            $sucessos = 0;
            $erros = 0;
            $errosDetalhes = [];

            // Mapeamento das colunas (ordem esperada na planilha)
            // Coluna 0: Nome (obrigatório)
            // Coluna 1: Nome de Contato
            // Coluna 2: Email de Contato
            // Coluna 3: Telefone de Contato
            // Coluna 4: Profissão
            // Coluna 5: Credencial
            // Coluna 6: Nº da Credencial
            // Coluna 7: Cidade/Estado

            foreach ($rows as $index => $row) {
                $linha = $index + 2; // +2 porque removemos o cabeçalho e arrays começam em 0

                // Função auxiliar para limpar valores do Excel
                $limparValor = function($valor) {
                    if ($valor === null || $valor === '') {
                        return null;
                    }
                    $valor = trim((string) $valor);
                    return $valor === '' ? null : $valor;
                };

                // Nome é obrigatório
                $nome = $limparValor($row[0] ?? null);
                if (empty($nome)) {
                    $erros++;
                    $errosDetalhes[] = "Linha {$linha}: Nome é obrigatório";
                    continue;
                }

                // Converte tudo para maiúsculas (exceto email e telefone)
                $nome = mb_strtoupper($nome, 'UTF-8');
                $nomeContato = $limparValor($row[1] ?? null);
                if ($nomeContato) {
                    $nomeContato = mb_strtoupper($nomeContato, 'UTF-8');
                }
                
                $emailContato = $limparValor($row[2] ?? null);
                
                $telefoneContato = $limparValor($row[3] ?? null);
                
                $profissao = $limparValor($row[4] ?? null);
                if ($profissao) {
                    $profissao = mb_strtoupper($profissao, 'UTF-8');
                }
                
                $credencial = $limparValor($row[5] ?? null);
                if ($credencial) {
                    $credencial = mb_strtoupper($credencial, 'UTF-8');
                }
                
                $numeroCredencial = $limparValor($row[6] ?? null);
                
                $cidadeEstado = $limparValor($row[7] ?? null);
                if ($cidadeEstado) {
                    $cidadeEstado = mb_strtoupper($cidadeEstado, 'UTF-8');
                }

                // Valida email se fornecido
                if ($emailContato && !filter_var($emailContato, FILTER_VALIDATE_EMAIL)) {
                    $erros++;
                    $errosDetalhes[] = "Linha {$linha}: Email inválido ({$emailContato})";
                    continue;
                }

                $dados = [
                    'empresa' => (int) $empresa,
                    'nome' => $nome,
                    'nome_contato' => $nomeContato,
                    'email_contato' => $emailContato,
                    'telefone_contato' => $telefoneContato,
                    'profissao' => $profissao,
                    'credencial' => $credencial,
                    'numero_credencial' => $numeroCredencial,
                    'cidade_estado' => $cidadeEstado,
                ];

                $result = $model->criar($dados);

                if ($result->getResult()) {
                    $sucessos++;
                } else {
                    $erros++;
                    $errosDetalhes[] = "Linha {$linha}: Erro ao salvar ({$nome})";
                }
            }

            $mensagem = "Importação concluída: {$sucessos} assistente(s) importado(s) com sucesso.";
            if ($erros > 0) {
                $mensagem .= " {$erros} erro(s) encontrado(s).";
            }

            $this->responseJson([
                'success' => true,
                'message' => $mensagem,
                'sucessos' => $sucessos,
                'erros' => $erros,
                'erros_detalhes' => $errosDetalhes
            ]);

        } catch (\Exception $e) {
            $this->responseJson([
                'success' => false,
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ]);
        }
    }
}
