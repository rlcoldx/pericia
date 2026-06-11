<?php

namespace Agencia\Close\Services\Parecer;

use Agencia\Close\Models\Assistente\Assistente;
use Agencia\Close\Models\Parecer\Parecer;
use Agencia\Close\Models\Reclamada\Reclamada;
use Agencia\Close\Models\Reclamante\Reclamante;

class ParecerSalvarService
{
    private const STATUS_PARECER_VALIDOS = [
        'OK',
        'FAVORAVEL',
        'DESFAVORAVEL',
        'PARCIAL FAVORAVEL',
        'NR (NÃO REALIZADO)',
    ];

    private const STATUS_REVISAO_VALIDOS = [
        'Revisão de Parecer',
        'Enviado',
    ];

    /**
     * @return array{success: bool, id: ?int, message: string}
     */
    public function criar(int $empresa, array $input, ?int $agendamentoId = null): array
    {
        $tipo = $this->normalizarTipo($input['tipo'] ?? '');
        $dataRealizacao = $this->normalizarData($input['data_realizacao'] ?? null);

        if ($dataRealizacao === null || $tipo === '') {
            return [
                'success' => false,
                'id' => null,
                'message' => 'Data da Realização e Tipo são obrigatórios.',
            ];
        }

        $this->garantirTipo($empresa, $tipo);

        $dados = $this->montarDados($empresa, $input, $agendamentoId);
        $dados['data_realizacao'] = $dataRealizacao;
        $dados['tipo'] = $tipo;

        $parecerModel = new Parecer();
        $result = $parecerModel->criar($dados);

        if (!$result->getResult()) {
            $driverMsg = $this->mensagemErroDriver($result);

            return [
                'success' => false,
                'id' => null,
                'message' => $driverMsg !== ''
                    ? ('Erro ao cadastrar parecer: ' . $driverMsg)
                    : 'Erro ao cadastrar parecer. Verifique os campos e tente novamente.',
            ];
        }

        return [
            'success' => true,
            'id' => (int) $result->getResult(),
            'message' => 'Parecer cadastrado com sucesso.',
        ];
    }

    /**
     * @return array{success: bool, id: ?int, message: string}
     */
    public function atualizar(int $id, int $empresa, array $input, ?int $agendamentoId = null): array
    {
        $tipo = $this->normalizarTipo($input['tipo'] ?? '');
        $dataRealizacao = $this->normalizarData($input['data_realizacao'] ?? null);

        if ($dataRealizacao === null || $tipo === '') {
            return [
                'success' => false,
                'id' => null,
                'message' => 'Data da Realização e Tipo são obrigatórios.',
            ];
        }

        $this->garantirTipo($empresa, $tipo);

        $dados = $this->montarDados($empresa, $input, $agendamentoId, false);
        $dados['data_realizacao'] = $dataRealizacao;
        $dados['tipo'] = $tipo;

        $parecerModel = new Parecer();
        $result = $parecerModel->atualizar($id, $empresa, $dados);

        if (!$result->getResult()) {
            $driverMsg = $this->mensagemErroDriver($result);

            return [
                'success' => false,
                'id' => null,
                'message' => $driverMsg !== ''
                    ? ('Erro ao atualizar parecer: ' . $driverMsg)
                    : 'Erro ao atualizar parecer. Verifique os campos e tente novamente.',
            ];
        }

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Parecer atualizado com sucesso.',
        ];
    }

    /**
     * Cria ou atualiza parecer vinculado a um agendamento.
     *
     * @return array{success: bool, id: ?int, message: string, skipped: bool}
     */
    public function salvarVinculadoAgendamento(int $empresa, int $agendamentoId, array $input): array
    {
        $dataRealizacao = $this->normalizarData($input['data_realizacao'] ?? null);
        $dataFatal = $this->normalizarData($input['data_fatal'] ?? null);
        $tipo = $this->normalizarTipo($input['tipo'] ?? '');

        if ($dataRealizacao === null || $dataFatal === null || $tipo === '') {
            return [
                'success' => true,
                'id' => null,
                'message' => '',
                'skipped' => true,
            ];
        }

        $input['data_realizacao'] = $dataRealizacao;
        $input['data_fatal'] = $dataFatal;
        $input['tipo'] = $tipo;
        $input['reclamada_id'] = $this->resolverReclamadaId(
            $empresa,
            $input['reclamada_id'] ?? null,
            $input['cliente_nome'] ?? null
        );
        $input['reclamante_id'] = $this->resolverReclamanteId(
            $empresa,
            $input['reclamante_id'] ?? null,
            $input['reclamante_nome'] ?? null
        );

        $parecerModel = new Parecer();
        $parecerExistente = $parecerModel->getPorAgendamento($agendamentoId, $empresa)->getResult()[0] ?? null;

        if ($parecerExistente) {
            $resultado = $this->atualizar((int) $parecerExistente['id'], $empresa, $input, $agendamentoId);
            $resultado['skipped'] = false;

            return $resultado;
        }

        $resultado = $this->criar($empresa, $input, $agendamentoId);
        $resultado['skipped'] = false;

        return $resultado;
    }

    public function montarDados(int $empresa, array $input, ?int $agendamentoId = null, bool $incluirEmpresa = true): array
    {
        $assistenteId = !empty($input['assistente_id']) ? (int) $input['assistente_id'] : null;
        $assistenteNome = $this->resolverAssistenteNome($empresa, $assistenteId, $input['assistente'] ?? null);

        $dados = [
            'numero_processo' => $this->normalizarTexto($input['numero_processo'] ?? null),
            'data_realizacao' => $this->normalizarData($input['data_realizacao'] ?? null),
            'data_fatal' => $this->normalizarData($input['data_fatal'] ?? null),
            'data_entrega_parecer' => $this->normalizarData($input['data_entrega_parecer'] ?? null),
            'status_parecer' => $this->sanitizarStatusParecer($input['status_parecer'] ?? null),
            'status_revisao' => $this->sanitizarStatusRevisao($input['status_revisao'] ?? null),
            'tipo' => $this->normalizarTipo($input['tipo'] ?? ''),
            'assistente' => $assistenteNome,
            'assistente_id' => $assistenteId,
            'reclamada_id' => !empty($input['reclamada_id']) ? (int) $input['reclamada_id'] : null,
            'reclamante_id' => !empty($input['reclamante_id']) ? (int) $input['reclamante_id'] : null,
            'funcoes' => $this->normalizarTexto($input['funcoes'] ?? null),
            'observacoes' => $this->normalizarTexto($input['observacoes'] ?? null),
        ];

        if ($incluirEmpresa) {
            $dados = array_merge(['empresa' => $empresa], $dados);
        }

        if ($agendamentoId !== null) {
            $dados['agendamento_id'] = $agendamentoId;
        }

        return $dados;
    }

    public function garantirTipo(int $empresa, string $tipo): void
    {
        $parecerModel = new Parecer();
        $tiposExistentes = $parecerModel->listarTipos($empresa)->getResult() ?? [];
        $tiposNomes = array_column($tiposExistentes, 'nome');

        if (!in_array($tipo, $tiposNomes, true)) {
            $parecerModel->criarTipo($empresa, $tipo);
        }
    }

    public function normalizarData($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        $texto = trim((string) $value);
        if ($texto === '') {
            return null;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $texto, $matches)) {
            return sprintf('%s-%s-%s', $matches[3], $matches[2], $matches[1]);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return $texto;
        }

        $timestamp = strtotime($texto);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    public function normalizarTipo($value): string
    {
        if (is_array($value)) {
            $value = $value[0] ?? '';
        }

        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    public function sanitizarStatusParecer($value): ?string
    {
        $texto = $this->normalizarTexto($value);
        if ($texto === null) {
            return null;
        }

        return in_array($texto, self::STATUS_PARECER_VALIDOS, true) ? $texto : null;
    }

    public function sanitizarStatusRevisao($value): ?string
    {
        $texto = $this->normalizarTexto($value);
        if ($texto === null) {
            return null;
        }

        return in_array($texto, self::STATUS_REVISAO_VALIDOS, true) ? $texto : null;
    }

    public function mensagemErroDriver(object $result): string
    {
        if (!method_exists($result, 'getErrorInfo')) {
            return '';
        }

        $ei = $result->getErrorInfo();
        if (!is_array($ei)) {
            return '';
        }

        if (isset($ei['driver_message']) && $ei['driver_message'] !== '') {
            return (string) $ei['driver_message'];
        }

        if (isset($ei[2]) && $ei[2] !== '') {
            return (string) $ei[2];
        }

        return '';
    }

    private function normalizarTexto($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        $texto = trim((string) $value);

        return $texto !== '' ? $texto : null;
    }

    private function resolverAssistenteNome(int $empresa, ?int $assistenteId, $assistenteInformado): ?string
    {
        $nomeInformado = $this->normalizarTexto($assistenteInformado);
        if ($nomeInformado !== null) {
            return $nomeInformado;
        }

        if (!$assistenteId) {
            return null;
        }

        $assistenteModel = new Assistente();
        $assistente = $assistenteModel->getPorId($assistenteId, $empresa)->getResult()[0] ?? null;

        return $assistente['nome'] ?? null;
    }

    private function resolverReclamadaId(int $empresa, $id, $nome): ?int
    {
        if (!empty($id)) {
            return (int) $id;
        }

        return $this->resolverIdPorNome(new Reclamada(), $empresa, $nome);
    }

    private function resolverReclamanteId(int $empresa, $id, $nome): ?int
    {
        if (!empty($id)) {
            return (int) $id;
        }

        return $this->resolverIdPorNome(new Reclamante(), $empresa, $nome);
    }

    /**
     * @param Reclamada|Reclamante $model
     */
    private function resolverIdPorNome(object $model, int $empresa, $nome): ?int
    {
        $nomeBusca = $this->normalizarTexto($nome);
        if ($nomeBusca === null) {
            return null;
        }

        $registros = $model->listar($empresa)->getResult() ?? [];
        foreach ($registros as $registro) {
            if (isset($registro['nome']) && strcasecmp(trim((string) $registro['nome']), $nomeBusca) === 0) {
                return (int) $registro['id'];
            }
        }

        return null;
    }
}
