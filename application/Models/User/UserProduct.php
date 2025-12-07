<?php

namespace Agencia\Close\Models\User;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Model;

class UserProduct extends Model
{

    public function findUserList($idCompany, $idUser, $idItem): Read
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT ul.*, (SELECT id_item FROM usuarios_listas_itens WHERE id_empresa = :idCompany AND id_user = :idUser AND id_lista = ul.id AND id_item = :idItem LIMIT 1) AS id_item
								FROM usuarios_listas AS ul WHERE ul.id_user = :idUser AND ul.id_empresa = :idCompany ORDER BY ul.`id` DESC", "idUser={$idUser}&idCompany={$idCompany}&idItem={$idItem}");
        return $this->read;
    }

    public function getLastList($idUser, $idCompany, $nameList)
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT * FROM usuarios_listas WHERE id_user = :idUser AND id_empresa = :idCompany AND nome = :nameList ORDER BY id DESC LIMIT 1", "idUser={$idUser}&idCompany={$idCompany}&nameList={$nameList}");
        $result = $this->read->getResult()[0];
        return $result['id'];
    }

    public function totalCompare($idUser, $idCompany)
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT * FROM usuarios_comparar WHERE id_user = :idUser AND empresa = :idCompany AND id_lista = '0'", "idUser={$idUser}&idCompany={$idCompany}");
        $result = $this->read->getRowCount();
        return $result;
    }

}