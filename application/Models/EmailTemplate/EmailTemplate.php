<?php

namespace Agencia\Close\Models\EmailTemplate;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class EmailTemplate extends Model
{
    protected Create $create;
    protected Read $read;
    protected Update $update;
    protected Delete $delete;

    public function __construct()
    {
        $this->create = new Create();
        $this->read = new Read();
        $this->update = new Update();
        $this->delete = new Delete();
    }

    /**
     * Lista todos os templates de e-mail da empresa
     */
    public function listar(int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'email_templates',
            'WHERE empresa = :empresa ORDER BY tipo ASC',
            "empresa={$empresa}"
        );
        return $this->read;
    }

    /**
     * Busca um template por ID
     */
    public function getPorId(int $id, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'email_templates',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->read;
    }

    /**
     * Busca um template por tipo
     */
    public function getPorTipo(string $tipo, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'email_templates',
            'WHERE tipo = :tipo AND empresa = :empresa AND ativo = 1',
            "tipo={$tipo}&empresa={$empresa}"
        );
        return $this->read;
    }

    /**
     * Cria um novo template
     */
    public function criar(array $data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('email_templates', $data);
        return $this->create;
    }

    /**
     * Atualiza um template
     */
    public function atualizar(int $id, array $data, int $empresa): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'email_templates',
            $data,
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->update;
    }

    /**
     * Remove um template
     */
    public function remover(int $id, int $empresa): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete(
            'email_templates',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->delete;
    }

    /**
     * Ativa todos os templates da empresa
     */
    public function ativarTodos(int $empresa): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'email_templates',
            ['ativo' => 1],
            'WHERE empresa = :empresa',
            "empresa={$empresa}"
        );
        return $this->update;
    }

    /**
     * Desativa todos os templates da empresa
     */
    public function desativarTodos(int $empresa): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'email_templates',
            ['ativo' => 0],
            'WHERE empresa = :empresa',
            "empresa={$empresa}"
        );
        return $this->update;
    }
}
