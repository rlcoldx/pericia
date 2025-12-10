<?php

namespace Agencia\Close\Models\Configuracao;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Models\Model;

class Configuracao extends Model
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

    /**
     * Busca configurações da empresa
     */
    public function getPorEmpresa(int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'configuracoes',
            'WHERE empresa = :empresa LIMIT 1',
            "empresa={$empresa}"
        );
        return $this->read;
    }

    /**
     * Cria configurações
     */
    public function criar(array $data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('configuracoes', $data);
        return $this->create;
    }

    /**
     * Atualiza configurações
     */
    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'configuracoes',
            $data,
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->update;
    }

    /**
     * Busca configurações ou retorna padrão
     */
    public function getConfiguracoesOuPadrao(int $empresa): array
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'configuracoes',
            'WHERE empresa = :empresa LIMIT 1',
            "empresa={$empresa}"
        );

        $result = $this->read->getResult();
        if (!empty($result)) {
            return $result[0];
        }

        // Retorna valores padrão do config.php se não existir no banco
        return [
            'mail_host' => defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com',
            'mail_email' => defined('MAIL_EMAIL') ? MAIL_EMAIL : '',
            'mail_user' => defined('MAIL_USER') ? MAIL_USER : '',
            'mail_password' => defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '',
            'mail_cc' => null,
        ];
    }
}
