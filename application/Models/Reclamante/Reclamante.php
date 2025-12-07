<?php

namespace Agencia\Close\Models\Reclamante;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Reclamante extends Model
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

    public function listar(int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'reclamantes',
            'WHERE empresa = :empresa ORDER BY nome ASC',
            "empresa={$empresa}"
        );
        return $this->read;
    }

    public function getPorId(int $id, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'reclamantes',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->read;
    }

    public function criar(array $data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('reclamantes', $data);
        return $this->create;
    }

    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'reclamantes',
            $data,
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->update;
    }

    public function remover(int $id, int $empresa): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete(
            'reclamantes',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->delete;
    }
}
