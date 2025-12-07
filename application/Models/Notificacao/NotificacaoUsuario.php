<?php

namespace Agencia\Close\Models\Notificacao;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Models\Model;

class NotificacaoUsuario extends Model
{
    protected Create $create;
    protected Read $read;
    protected Update $update;

    public function __construct()
    {
        $this->create = new Create();
        $this->read = new Read();
        $this->update = new Update();
    }

    public function criarParaUsuario(
        int $usuarioId, 
        string $titulo, 
        string $mensagem, 
        ?string $url = null,
        ?string $tipo = null,
        ?string $modulo = null,
        ?string $acao = null,
        ?int $registroId = null
    ): Create
    {
        $this->create = new Create();
        $data = [
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'url' => $url,
            'lido' => 0,
        ];

        if ($tipo !== null) {
            $data['tipo'] = $tipo;
        }
        if ($modulo !== null) {
            $data['modulo'] = $modulo;
        }
        if ($acao !== null) {
            $data['acao'] = $acao;
        }
        if ($registroId !== null) {
            $data['registro_id'] = $registroId;
        }

        $this->create->ExeCreate('notificacoes', $data);

        return $this->create;
    }

    public function criarParaUsuarios(
        array $usuariosIds, 
        string $titulo, 
        string $mensagem, 
        ?string $url = null,
        ?string $tipo = null,
        ?string $modulo = null,
        ?string $acao = null,
        ?int $registroId = null
    ): void
    {
        foreach ($usuariosIds as $usuarioId) {
            $this->criarParaUsuario((int) $usuarioId, $titulo, $mensagem, $url, $tipo, $modulo, $acao, $registroId);
        }
    }

    public function listarPorUsuario(int $usuarioId): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'notificacoes',
            'WHERE usuario_id = :usuario_id ORDER BY lido ASC, data_create DESC',
            "usuario_id={$usuarioId}"
        );

        return $this->read;
    }

    public function marcarComoLida(int $id, int $usuarioId): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'notificacoes',
            ['lido' => 1],
            'WHERE id = :id AND usuario_id = :usuario_id',
            "id={$id}&usuario_id={$usuarioId}"
        );

        return $this->update;
    }

    public function marcarEmailEnviado(int $usuarioId, string $tipo, string $modulo, string $acao, ?int $registroId = null): Update
    {
        $this->update = new Update();
        $where = 'WHERE usuario_id = :usuario_id AND tipo = :tipo AND modulo = :modulo AND acao = :acao';
        $params = "usuario_id={$usuarioId}&tipo={$tipo}&modulo={$modulo}&acao={$acao}";
        
        if ($registroId !== null) {
            $where .= ' AND registro_id = :registro_id';
            $params .= "&registro_id={$registroId}";
        }
        
        $this->update->ExeUpdate(
            'notificacoes',
            [
                'email_enviado' => 1,
                'data_email_enviado' => date('Y-m-d H:i:s')
            ],
            $where,
            $params
        );

        return $this->update;
    }
}

