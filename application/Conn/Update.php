<?php

namespace Agencia\Close\Conn;

use PDO;
use PDOException;
use PDOStatement;

/**
 * <b>Update.class:</b>
 * Classe responsável por atualizações genéticas no banco de dados!
 * 
 */
class Update extends Conn {

    private $Tabela;
    private $Dados;
    private $Termos;
    private $Places;
    private $Result;
    private $ErrorInfo;

    /** @var PDOStatement */
    private $Update;

    /** @var PDO */
    private $Conn;

    /**
     * <b>Exe Update:</b> Executa uma atualização simplificada com Prepared Statments. Basta informar o 
     * nome da tabela, os dados a serem atualizados em um Attay Atribuitivo, as condições e uma 
     * analize em cadeia (ParseString) para executar.
     * @param STRING $Tabela = Nome da tabela
     * @param ARRAY $Dados = [ NomeDaColuna ] => Valor ( Atribuição )
     * @param STRING $Termos = WHERE coluna = :link AND.. OR..
     * @param STRING $ParseString = link={$link}&link2={$link2}
     */
    public function ExeUpdate($Tabela, array $Dados, $Termos, $ParseString) {
        $this->Tabela = (string) $Tabela;
        $this->Dados = $Dados;
        $this->Termos = (string) $Termos;

        parse_str($ParseString, $this->Places);
        $this->getSyntax();
        $this->Execute();
    }

    /**
     * <b>Obter resultado:</b> Retorna TRUE se não ocorrer erros, ou FALSE. Mesmo não alterando os dados se uma query
     * for executada com sucesso o retorno será TRUE. Para verificar alterações execute o getRowCount();
     * @return BOOL $Var = True ou False
     */
    public function getResult() {
        return $this->Result;
    }

    /**
     * <b>Contar Registros: </b> Retorna o número de linhas alteradas no banco!
     * @return INT $Var = Quantidade de linhas alteradas
     */
    public function getRowCount() {
        try {
            if (isset($this->Update) && method_exists($this->Update, 'rowCount')) {
                return $this->Update->rowCount();
            }
        } catch (\Exception $e) {
            // Silenciar erro de tipo
        }
        return 0;
    }

    public function FullUpdate($Query, $ParseString = null) {
        $this->Update = (string) $Query;
        if (!empty($ParseString)):
            parse_str($ParseString, $this->Places);
        endif;
        $this->Execute();
    }

    /**
     * <b>Modificar Links:</b> Método pode ser usado para atualizar com Stored Procedures. Modificando apenas os valores
     * da condição. Use este método para editar múltiplas linhas!
     * @param STRING $ParseString = id={$id}&..
     */
    public function setPlaces($ParseString) {
        parse_str($ParseString, $this->Places);
        $this->getSyntax();
        $this->Execute();
    }

    /**
     * ****************************************
     * *********** PRIVATE METHODS ************
     * ****************************************
     */
    //Obtém o PDO e Prepara a query
    private function Connect() {
        $this->Conn = $this->getConn();
        $this->Update = $this->Conn->prepare($this->Update);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax() {
        foreach ($this->Dados as $Key => $Value):
            // Adicionar backticks para palavras reservadas do SQL
            $Places[] = "`{$Key}` = :{$Key}";
        endforeach;

        $Places = implode(', ', $Places);
        $this->Update = "UPDATE {$this->Tabela} SET {$Places} {$this->Termos}";
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function Execute() {
        $this->Connect();
        try {
            if(isset($this->Dados)):
                $this->Update->execute(array_merge($this->Dados, $this->Places));
            else:
                $this->Update->execute();
            endif;
            $this->Result = true;
            $this->ErrorInfo = null;
        } catch (PDOException $e) {
            $this->Result = null;
            $this->ErrorInfo = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sqlstate' => $e->errorInfo[0] ?? null,
                'driver_code' => $e->errorInfo[1] ?? null,
                'driver_message' => $e->errorInfo[2] ?? null
            ];
            // EchoMsg("<b>Erro ao Ler:</b> {$e->getMessage()}", $e->getCode());
        }
    }

    /**
     * Retorna informações sobre o erro ocorrido
     * @return array|null
     */
    public function getErrorInfo() {
        return $this->ErrorInfo;
    }

}
