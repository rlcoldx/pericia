<?php

namespace Agencia\Close\Helpers;

class Check {

    private static $Data;
    private static $Format;
    private static $FormatTwo;

    /**
     * <b>Verifica E-mail:</b> Executa validação de formato de e-mail. Se for um email válido retorna true, ou retorna false.
     * @param STRING $Email = Uma conta de e-mail
     * @return BOOL = True para um email válido, ou false
     */
    public static function Email($Email) {
        self::$Data = (string) $Email;
        self::$Format = '/[a-z0-9_\.\-]+@[a-z0-9_\.\-]*[a-z0-9_\.\-]+\.[a-z]{2,4}$/';

        if (preg_match(self::$Format, self::$Data)):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * <b>Verifica E-mail do Gmail:</b> Executa validação de formato de e-mail do Gmail. Se for um email válido retorna true, ou retorna false.
     * @param STRING $Email = Uma conta de e-mail
     * @return BOOL = True para um email do gmail válido, ou false
     */
    public static function EmailGmail($Email) {
        self::$Data = (string) $Email;
        self::$Format = '/[a-zA-Z0-9_\.\-]+@gmail.com$/';

        if (preg_match(self::$Format, self::$Data)):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * <b>Verifica E-mail do Hotmail:</b> Executa validação de formato de e-mail do Hotmail. Se for um email válido retorna true, ou retorna false.
     * @param STRING $Email = Uma conta de e-mail
     * @return BOOL = True para um email do hotmail válido, ou false
     */
    public static function EmailHotmail($Email) {
        self::$Data = (string) $Email;
        self::$Format = '/[a-zA-Z0-9_\.\-]+@hotmail.com$/';
        self::$FormatTwo = '/[a-zA-Z0-9_\.\-]+@live.com$/';

        if (preg_match(self::$Format, self::$Data) || preg_match(self::$FormatTwo, self::$Data)):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * <b>Tranforma URL:</b> Tranforma uma string no formato de URL amigável e retorna o a string convertida!
     * @param STRING $Name = Uma string qualquer
     * @return STRING = $Data = Uma URL amigável válida
     */
    public static function Name($Name) {
        self::$Format = array();
        self::$Format['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª';
        self::$Format['b'] = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                 ';

        self::$Data = strtr($Name, self::$Format['a'], self::$Format['b']);
        self::$Data = strip_tags(trim(self::$Data));
        self::$Data = str_replace(' ', '-', self::$Data);
        self::$Data = str_replace(array('-----', '----', '---', '--'), '-', self::$Data);

        return strtolower(self::$Data);
    }

    /**
     * <b>Limpar Name:</b> Limpa a string
     * @param STRING $Name = Uma string qualquer
     * @return STRING = $Data = Uma string limpa de caracteres inválidos
     */
    public static function LimpaString($String) {
        self::$Format = array();
        self::$Format['a'] = '"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª¨';
        self::$Format['b'] = '                                  ';

        self::$Data = strtr($String, self::$Format['a'], self::$Format['b']);
        self::$Data = str_replace(' ', '', self::$Data);
        self::$Data = strip_tags(trim(self::$Data));

        return self::$Data;
    }


    /**
     * <b>Converte em:</b> função para transformar strings em maiúscula ou minúscula com acentos
     * @param STRING $term = Uma string qualquer
     * @param STRING $tp = tipo da conversão: 1 para maiúsculas e 0 para minúsculas
     * @return STRING $palavra string convertida
     */
    public static function ConverteEm($term, $tp) { 
        if ($tp == "1") $palavra = strtr(strtoupper($term),"àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ","ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß"); 
        elseif ($tp == "0") $palavra = strtr(strtolower($term),"ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß","àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ"); 
        return $palavra; 
    } 

    /**
     * <b>Checar Nome:</b> checa se nome possui pelo menos um espaço para ser nome completo
     * @param STRING $Name = Uma string qualquer
     * @return BOOL = True para um nome válido, ou false
     */
    public static function ChecaNome($String) {
        self::$Data = $String;
        if(strlen(self::$Data) <= strlen(str_replace(' ', '', self::$Data))):
            return false;
        else:
            return true;
        endif;
    }

    /**
     * <b>Busca Abreviação do Nome:</b> retona uma abreviação do nome com a quantidade de caracteres informada
     * @param STRING $String = Uma string qualquer
     * @param INT $Quantidade = Quantidade de Caracteres desejada
     * @return STRING $Abreviacao = Uma abreviação do nome
     */
    public static function BuscaAbreviacaoNome($String, $Quantidade = 2){
        self::$Data = $String;
        self::$Format = $Quantidade;
        $pieces = explode(' ', self::$Data);
        $Abreviacao = '';
        foreach($pieces as $piece):
            if(strlen($piece) > 3):
                $Abreviacao .= (strlen($Abreviacao) < self::$Format) ? strtoupper(substr($piece, 0, 1)) : '';
            endif;
        endforeach;
        return $Abreviacao;
    }

    /**
     * <b>Checar URL:</b> checa se url é válida
     * @param STRING $Url = Uma url qualquer
     * @return BOOL = True para uma url válida, ou false
     */
    public static function ChecaUrl($Url) {
        self::$Data = $Url;
        if (filter_var($Url, FILTER_VALIDATE_URL) === false):
            return false;
        else:
            return true;
        endif;
    }


    /**
     * <b>Checar Número:</b> checa se string possui apenas números
     * @param STRING $String = Uma string qualquer
     * @return BOOL = True para uma string que tenha apenas números, ou false
     */
    public static function ChecaNumero($String) {
        self::$Data = self::LimpaString($String);
        if(!is_numeric(self::$Data)):
            return false;
        else:
            return true;
        endif;
    }

    /**
     * <b>Checa senha:</b> Checa se senha não contém caracteres especiais e o número máximo e mínimo de elementos
     * @param STRING $Senha = A senha a ser checada
     * @param INT $Min = Número mínimo de caracteres
     * @param INT $Max = Número máximo de caracteres
     * @return BOOL = True para uma senha válida, ou false
     */
    public static function Senha($Senha, $Min, $Max) {
        self::$Data = (string) $Senha;

        if (ctype_alnum(self::$Data)):
            if(strlen(self::$Data) < $Min || strlen(self::$Data) > $Max):
                return false;
            else:
                return true;
            endif;
        else:
            return false;
        endif;
    }


    /**
     * <b>Tranforma Data:</b> Transforma uma data no formato DD/MM/YY em uma data no formato TIMESTAMP!
     * @param STRING $Name = Data em (d/m/Y) ou (d/m/Y H:i:s)
     * @return STRING = $Data = Data no formato timestamp!
     */
    public static function Data($Data) {
        self::$Format = explode(' ', $Data);
        self::$Data = explode('/', self::$Format[0]);

        if (empty(self::$Format[1])):
            self::$Format[1] = date('H:i:s');
        endif;

        self::$Data = self::$Data[2] . '-' . self::$Data[1] . '-' . self::$Data[0] . ' ' . self::$Format[1];
        return self::$Data;
    }

    /**
     * <b>Tranforma Data:</b> Transforma uma data no formato MM-DD-YYYY em uma data no formato DD/MM/YYYY!
     * @param STRING $Name = Data em (YYYY-MM-DD)
     * @param INT $NumRetorno = Número de data a ser retornada (ex: 2 retorna DD/MM)
     * @return STRING = $Data = Data no formato DD/MM/YYYY!
     */
    public static function DataBanco($Data, $NumRetorno = null) {
        self::$Format = explode('-', $Data);

        if($NumRetorno == 1):
            self::$Data = self::$Format[2];
        elseif($NumRetorno == 2):
            self::$Data = self::$Format[2] . '/' . self::$Format[1];
        else:
            self::$Data = self::$Format[2] . '/' . self::$Format[1] . '/' . self::$Format[0];
        endif;

        return self::$Data;
    }

    /**
     * <b>Formata Data:</b> Transforma uma data em um formato de acordo com a linguagem escolhida
     * @param STRING $Name = data no formato timestamp
     * @return STRING = $Data = data no formato da linguagem escolhida
     */
    public static function FormataData($Data, $Linguagem = 'pt', $Fuso = null, $Conteudo = null) {
        self::$Format = strtotime($Data);

        if($Fuso != null):
            $UTC = new DateTimeZone("UTC");
            $newTZ = new DateTimeZone($Fuso);
            $date = new DateTime($Data, $UTC);
            $date->setTimezone($newTZ);
            self::$Format = strtotime($date->format('Y-m-d H:i:s'));
        endif;

        if($Linguagem == 'pt'):
            self::$Data = date('d', self::$Format).' de '.Check::PegaMes(date('m', self::$Format), $Linguagem).' de '.date('Y', self::$Format);
        elseif($Linguagem == 'en'):
            self::$Data = Check::PegaMes(date('m', self::$Format), $Linguagem).' '.date('d', self::$Format).', '.date('Y', self::$Format);
        elseif($Linguagem == 'es'):
            self::$Data = date('d', self::$Format).' de '.Check::PegaMes(date('m', self::$Format), $Linguagem).' de '.date('Y', self::$Format);
        endif;

        if($Conteudo == 'full'):
            self::$Data .= ' - '.date('H:i:s', self::$Format);
        elseif($Conteudo == 'hour'):
            self::$Data = date('H', self::$Format);
        endif;

        return self::$Data;
    }

    /**
     * <b>Formata Data Numerica:</b> Transforma uma data em um formato de acordo com a linguagem escolhida
     * @param STRING $Name = data no formato timestamp
     * @return STRING = $Data = data no formato da linguagem escolhida
     */
    public static function FormataDataNumerica($Data, $Linguagem = 'pt', $Fuso = null, $Conteudo = null) {
        self::$Format = strtotime($Data);

        if($Fuso != null):
            $UTC = new DateTimeZone("UTC");
            $newTZ = new DateTimeZone($Fuso);
            $date = new DateTime($Data, $UTC);
            $date->setTimezone($newTZ);
            self::$Format = strtotime($date->format('Y-m-d H:i:s'));
        endif;

        if($Linguagem == 'pt'):
            self::$Data = date('d', self::$Format).'/'.date('m', self::$Format).'/'.date('Y', self::$Format);
        elseif($Linguagem == 'en'):
            self::$Data = date('m', self::$Format).'/'.date('d', self::$Format).'/'.date('Y', self::$Format);
        elseif($Linguagem == 'es'):
            self::$Data = date('d', self::$Format).'.'.date('m', self::$Format).'.'.date('Y', self::$Format);
        endif;

        if($Conteudo == 'full'):
            self::$Data .= ' - '.date('H:i:s', self::$Format);
        elseif($Conteudo == 'timestampfull'):
            self::$Data .= ' '.date('H:i:s', self::$Format);
        elseif($Conteudo == 'timestamp'):
            self::$Data .= ' '.date('H:i', self::$Format);
        endif;

        return self::$Data;
    }

    /**
     * <b>Formata Data Timestamp:</b> Transforma data timestamp de acordo com o fuso passado
     * @param STRING $Name = data no formato timestamp
     * @return STRING = $Data = data no formato timestamp de acordo com o fuso escolhido
     */
    public static function FormataDataTimestamp($Data, $Fuso) {
        $UTC = new DateTimeZone("UTC");
        $newTZ = new DateTimeZone($Fuso);
        $date = new DateTime($Data, $UTC);
        $date->setTimezone($newTZ);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * <b>Formata Data Timestamp para UTC:</b> Transforma data timestamp do fuso passado para o fuso UTC
     * @param STRING $Name = data no formato timestamp
     * @return STRING = $Data = data no formato timestamp de acordo com o fuso escolhido
     */
    public static function FormataDataTimestampUTC($Data, $Fuso = 'America/Sao_Paulo') {
        $FusoAtual = new DateTimeZone($Fuso);
        $newTZ = new DateTimeZone("UTC");
        $date = new DateTime($Data, $FusoAtual);
        $date->setTimezone($newTZ);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * <b>Time Zone List:</b> lista todas as Timezones
     * @return ARRAY: um array com todas as timezones
     */
    public static function timezoneList(){
        $timezoneIdentifiers = DateTimeZone::listIdentifiers();
        $utcTime = new DateTime('now', new DateTimeZone('UTC'));

        $tempTimezones = array();
        foreach ($timezoneIdentifiers as $timezoneIdentifier) {
            $currentTimezone = new DateTimeZone($timezoneIdentifier);

            $tempTimezones[] = array(
                'offset' => (int)$currentTimezone->getOffset($utcTime),
                'identifier' => $timezoneIdentifier
            );
        }

        // Sort the array by offset,identifier ascending
        usort($tempTimezones, function($a, $b) {
            return ($a['offset'] == $b['offset'])
                ? strcmp($a['identifier'], $b['identifier'])
                : $a['offset'] - $b['offset'];
        });

        $timezoneList = array();
        foreach ($tempTimezones as $tz) {
            $sign = ($tz['offset'] > 0) ? '+' : '-';
            $offset = gmdate('H:i', abs($tz['offset']));
            $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' .
                $tz['identifier'];
        }

        return $timezoneList;
    }

    /**
     * <b>Pega mês:</b> Transforma um mês em inteiro para o mês em forma escrita segundo a língua escolhida!
     * @param INT $Mes, STRING $Lang
     * @return STRING = mÊs na forma escrita segundo a língua escolhida
     */
    public static function PegaMes($Mes, $Lang) {
        $Mes = (int) $Mes - 1;

        if($Lang == 'pt'):
            $array = array("janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro");
        elseif($Lang == 'en'):
            $array = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        elseif($Lang == 'es'):
            $array = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
        elseif($Lang == 'fr'):
            $array = array("janvier","février","mars","avril","mai","juin","juillet","août","septembre","octobre","novembre","décembre");
        endif;

        $dia = ($array[$Mes]) ? $array[$Mes] : '';
        return $dia;
    }

    /**
     * <b>Limita os Palavras:</b> Limita a quantidade de palavras a serem exibidas em uma string!
     * @param STRING $String = Uma string qualquer
     * @return INT = $Limite = String limitada pelo $Limite
     */
    public static function Words($String, $Limite, $Pointer = null) {
        self::$Data = strip_tags(trim($String));
        self::$Format = (int) $Limite;

        $ArrWords = explode(' ', self::$Data);
        $NumWords = count($ArrWords);
        $NewWords = implode(' ', array_slice($ArrWords, 0, self::$Format));

        $Pointer = (empty($Pointer) ? '...' : ' ' . $Pointer );
        $Result = ( self::$Format < $NumWords ? $NewWords . $Pointer : self::$Data );
        return $Result;
    }

    /**
     * <b>Obter categoria:</b> Informe o name (url) de uma categoria para obter o ID da mesma.
     * @param STRING $category_name = URL da categoria
     * @return INT $category_id = id da categoria informada
     */
    public static function CatByName($CategoryName) {
        $read = new Read;
        $read->ExeRead('ws_categories', "WHERE category_name = :name", "name={$CategoryName}");
        if ($read->getRowCount()):
            $resultado = $read->getResult();
            return $resultado[0]['category_id'];
        else:
            echo "A categoria {$CategoryName} não foi encontrada!";
            die;
        endif;
    }

    /**
     * <b>Usuários Online:</b> Ao executar este HELPER, ele automaticamente deleta os usuários expirados. Logo depois
     * executa um READ para obter quantos usuários estão realmente online no momento!
     * @return INT = Qtd de usuários online
     */
    public static function UserOnline() {
        date_default_timezone_set('America/Sao_Paulo');
        $now = date('Y-m-d H:i:s');
        $deleteUserOnline = new Delete;
        $deleteUserOnline->ExeDelete('ws_siteviews_online', "WHERE online_endview < :now", "now={$now}");

        $readUserOnline = new Read;
        $readUserOnline->FullRead("SELECT DISTINCT(online_session) AS users FROM ws_siteviews_online
                  WHERE online_endview > '$now'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%facebookexternalhit%'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%Googlebot%'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%bingbot%'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%Google favicon%'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%Synapse%'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%WWW-Mechanize%'
                  AND `ws_siteviews_online`.`online_agent` NOT LIKE '%AddThis.com%'");
        return $readUserOnline->getRowCount();
    }

    /**
     * <b>Imagem Upload:</b> Ao executar este HELPER, ele automaticamente verifica a existencia da imagem na pasta
     * uploads. Se existir retorna a imagem redimensionada!
     * @return HTML = imagem redimencionada!
     */
    public static function Image($ImageUrl, $ImageDesc, $ImageW = null, $ImageH = null, $Classe = null, $Position = 't') {

        self::$Data = $ImageUrl;

        if (file_exists(self::$Data) && !is_dir(self::$Data)):
            $patch = HOME;
            $imagem = self::$Data;
            return "<img class=\"{$Classe}\" src=\"{$patch}/tim.php?src={$patch}/{$imagem}&w={$ImageW}&h={$ImageH}&a={$Position}\" alt=\"{$ImageDesc}\" title=\"{$ImageDesc}\"/>";
        else:
            return false;
        endif;
    }

    /**
     * <b>Obter número status Paypal:</b> Informe o status do Paypal para receber o número do status do mesmo
     * @param STRING $Status = valor do status 
     * @return INT $numStatus = número do status no sistema    
     */
    public static function StatusPagamentoNumeroPaypal($Status){
        switch($Status):
            case 'Canceled_Reversal':
                $numStatus = 11;
            break;
            case 'Completed':
                $numStatus = 12;
            break;
            case 'Denied':
                $numStatus = 13;
            break;
            case 'Expired':
                $numStatus = 14;
            break;
            case 'Failed':
                $numStatus = 15;
            break;
            case 'Pending':
                $numStatus = 16;
            break;
            case 'Refunded':
                $numStatus = 17;
            break;
            case 'Reversed':
                $numStatus = 18;
            break;
            case 'Processed':
                $numStatus = 19;
            break;
            case 'Voided':
                $numStatus = 20;
            break;
            default:
                $numStatus = 0;
        endswitch;

        return $numStatus;
    }

    /**
     * <b>Obter número status PagHiper:</b> Informe o status do PagHiper para receber o número do status do mesmo
     * @param STRING $Status = valor do status 
     * @return INT $numStatus = número do status no sistema    
     */
    public static function StatusPagamentoNumeroPagHiper($Status){
        switch($Status):
            case 'Aguardando':
                $numStatus = 21;
            break;
            case 'Aprovado':
                $numStatus = 22;
            break;
            case 'Completo':
                $numStatus = 23;
            break;
            case 'Cancelado':
                $numStatus = 24;
            break;
            case 'Disputa':
                $numStatus = 25;
            break;
            default:
                $numStatus = 0;
        endswitch;

        return $numStatus;
    }

    /**
     * <b>Obter status:</b> Informe o id do status de um pedido para receber o status do mesmo
     * @param INT $numstatus = valor do status 
     * @return STRING $Status = valor do status em STRING
     */
    public static function StatusPagamento($numstatus){
        // 1 a 7: pagseguro
        // 11 a 20: paypal

        switch($numstatus):
            case '1':
                $Status = 'Aguardando';
                $label = 'primary';
            break;
            case '2':
                $Status = 'Em análise';
                $label = 'purple';
            break;
            case '3':
                $Status = 'Paga';
                $label = 'info';
            break;
            case '4':
                $Status = 'Disponível';
                $label = 'success';
            break;
            case '5':
                $Status = 'Em disputa';
                $label = 'danger';
            break;
            case '6':
                $Status = 'Devolvida';
                $label = 'default';
            break;
            case '7':
                $Status = 'Cancelada';
                $label = 'warning';
            break;

            case '11':
                $Status = 'Reversão Cancelada';
                $label = 'teal';
            break;
            case '12':
                $Status = 'Pagamento via Paypal';
                $label = 'success';
            break;
            case '13':
                $Status = 'Negada';
                $label = 'orange';
            break;
            case '14':
                $Status = 'Expirada';
                $label = 'black';
            break;
            case '15':
                $Status = 'Falha';
                $label = 'danger';
            break;
            case '16':
                $Status = 'Em análise';
                $label = 'purple';
            break;
            case '17':
                $Status = 'Reembolso emitido';
                $label = 'navy';
            break;
            case '18':
                $Status = 'Devolvida';
                $label = 'default';
            break;
            case '19':
                $Status = 'Paga';
                $label = 'info';
            break;
            case '20':
                $Status = 'Cancelada';
                $label = 'warning';
            break;

            case '21':
                $Status = 'Aguardando';
                $label = 'primary';
            break;
            case '22':
                $Status = 'Pagamento via PagHiper';
                $label = 'success';
            break;
            case '23':
                $Status = 'Pagamento via PagHiper';
                $label = 'success';
            break;
            case '24':
                $Status = 'Cancelado';
                $label = 'warning';
            break;
            case '25':
                $Status = 'Disputa';
                $label = 'danger';
            break;

            case '90':
                $Status = 'Professor';
                $label = 'success';
            break;

            case '91':
                $Status = 'Isento';
                $label = 'default';
            break;

            case '92':
                $Status = 'Trancado';
                $label = 'default';
            break;

            case '96':
                $Status = 'Pagamento via dinheiro';
                $label = 'success';
            break;
            case '97':
                $Status = 'Pagamento via depósito';
                $label = 'success';
            break;
            case '98':
                $Status = 'Pagamento via boleto';
                $label = 'success';
            break;
            default:
                $Status = 'sem status';
                $label = 'gray';
        endswitch;

        $Descricao = self::StatusPagamentoDescricao($numstatus);

        return '<span class="label label-'.$label.'" title="'.$Descricao.'" data-toggle="tooltip" data-placement="bottom">'.$Status.'</span>';
    }

    /**
     * <b>Obter status de situação da nota fiscal:</b> Informe o id da situação de uma nota fiscal para receber o status do mesmo
     * @param INT $numstatus = valor do status 
     * @return STRING $Status = valor do status em STRING
     */

    public static function StatusSituacaoNotaFiscal($numstatus){
        switch($numstatus):
            case '0':
                $Status = 'Não enviada';
                $label = 'default';
            break;
            case '1':
                $Status = 'Rejeitada';
                $label = 'danger';
            break;
            case '2':
                $Status = 'Autorizada';
                $label = 'success';
            break;
            case '3':
                $Status = 'Aguardando protocolo ou recibo de entrega';
                $label = 'purple';
            break;
            case '4':
                $Status = 'Denegada';
                $label = 'warning';
            break;
            case '5':
                $Status = 'Exceção';
                $label = 'danger';
            break;
            case '6':
                $Status = 'Nota fiscal não localizada';
                $label = 'default';
            break;
            case '7':
                $Status = 'Erros nos parâmetros enviados para a emissão da NFe';
                $label = 'danger';
            break;
        endswitch;

        return '<span class="label label-'.$label.'" data-toggle="tooltip" data-placement="bottom">'.$Status.'</span>';
    }

    /**
     * <b>Obter descrição do status:</b> Informe o id do status de um pedido para receber a descrição do status do mesmo
     * @param INT $numstatus = valor do status 
     * @return STRING $Descrição = descrição a respeito do status     
     */
    public static function StatusPagamentoDescricao($numstatus){
        // 1 a 7: pagseguro
        // 11 a 20: paypal

        switch($numstatus):
            case '1':
                $Descricao = 'O comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.';
            break;
            case '2':
                $Descricao = 'O comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.';
            break;
            case '3':
                $Descricao = 'A transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento.';
            break;
            case '4':
                $Descricao = 'A transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta.';
            break;
            case '5':
                $Descricao = 'O comprador, dentro do prazo de liberação da transação, abriu uma disputa.';
            break;
            case '6':
                $Descricao = 'O valor da transação foi devolvido para o comprador.';
            break;
            case '7':
                $Descricao = 'A transação foi cancelada sem ter sido finalizada.';
            break;

            case '11':
                $Descricao = 'Existia uma disputa e os valores que tinham sido revertidos anteriormente, voltaram para sua conta.';
            break;
            case '12':
                $Descricao = 'A transação está completa e o valor foi depositado em sua conta.';
            break;
            case '13':
                $Descricao = 'A transação foi negada.';
            break;
            case '14':
                $Descricao = 'A autorização expirou e não pode mais ser capturada.';
            break;
            case '15':
                $Descricao = 'O pagamento falhou. Esse valor apenas ocorre, caso o cliente tenha utilizado sua conta em bancária para fazer o pagamento.';
            break;
            case '16':
                $Descricao = 'O pagamento está pendente de revisão.';
            break;
            case '17':
                $Descricao = 'Um reembolso foi emitido.';
            break;
            case '18':
                $Descricao = 'O pagamento foi revertido. O valor que havia sido pago foi removido da sua conta e devolvido para a conta do cliente.';
            break;
            case '19':
                $Descricao = 'O pagamento foi aceito.';
            break;
            case '20':
                $Descricao = 'A autorização foi cancelada.';
            break;

            case '21':
                $Descricao = 'Pedido foi gerado e está aguardando a confirmação do pagamento.';
            break;
            case '22':
                $Descricao = 'Pagamento foi aprovado e aguarda compensar.';
            break;
            case '23':
                $Descricao = 'Pagamento foi compensado e está disponível para saque.';
            break;
            case '24':
                $Descricao = 'Pagamento não efetuado.';
            break;
            case '25':
                $Descricao = 'Pagamento contestado pelo comprador.';
            break;

            case '90':
                $Descricao = 'O aluno é professor no curso.';
            break;

            case '91':
                $Descricao = 'Essa fatura está isenta de pagamento.';
            break;

            case '92':
                $Descricao = 'O aluno trancou o curso, mas já havia pago essa parcela anteriormente.';
            break;

            case '96':
                $Descricao = 'O pagamento foi efetuado por dinheiro em espécie.';
            break;
            case '97':
                $Descricao = 'O pagamento foi efetuado via depósito bancário.';
            break;
            case '98':
                $Descricao = 'O pagamento foi efetuado via boleto bancário.';
            break;
            default:
                $Descricao = '-';
        endswitch;

        return $Descricao;
    }

    /**
     * <b>Obter status na visão do Usuário:</b> Informe o id do status de um pedido para receber o status do mesmo
     * @param INT $numstatus = valor do status 
     * @return STRING $Status = valor do status em STRING
     */
    public static function StatusPagamentoUsuario($numstatus){
        // 1 a 7: pagseguro
        // 11 a 20: paypal

        switch($numstatus):
            case '1':
                $Status = _e('Aguardando');
                $label = 'primary';
            break;
            case '2':
                $Status = _e('Em análise');
                $label = 'purple';
            break;
            case '3':
            case '4':
                $Status = _e('Paga');
                $label = 'success';
            break;
            case '5':
                $Status = _e('Em disputa');
                $label = 'danger';
            break;
            case '6':
                $Status = _e('Devolvida');
                $label = 'default';
            break;
            case '7':
                $Status = _e('Cancelada');
                $label = 'warning';
            break;

            case '11':
                $Status = _e('Reversão Cancelada');
                $label = 'teal';
            break;
            case '12':
            case '19':
                $Status = _e('Paga');
                $label = 'success';
            break;
            case '13':
                $Status = _e('Negada');
                $label = 'orange';
            break;
            case '14':
                $Status = _e('Expirada');
                $label = 'black';
            break;
            case '15':
                $Status = _e('Falha');
                $label = 'danger';
            break;
            case '16':
                $Status = _e('Em análise');
                $label = 'purple';
            break;
            case '17':
                $Status = _e('Reembolso emitido');
                $label = 'navy';
            break;
            case '18':
                $Status = _e('Devolvida');
                $label = 'default';
            break;
            case '20':
                $Status = _e('Cancelada');
                $label = 'warning';
            break;

            case '21':
                $Status = _e('Aguardando');
                $label = 'primary';
            break;
            case '22':
            case '23':
                $Status = _e('Paga');
                $label = 'success';
            break;
            case '24':
                $Status = _e('Cancelada');
                $label = 'warning';
            break;
            case '25':
                $Status = _e('Disputa');
                $label = 'danger';
            break;

            case '90':
                $Status = _e('Paga');
                $label = 'success';
            break;

            case '91':
                $Status = _e('Isento');
                $label = 'default';
            break;

            case '92':
                $Status = _e('Paga');
                $label = 'success';
            break;

            case '96':
                $Status = _e('Paga');
                $label = 'success';
            break;
            case '97':
                $Status = _e('Paga');
                $label = 'success';
            break;
            case '98':
                $Status = _e('Paga');
                $label = 'success';
            break;
            default:
                $Status = _e('sem status');
                $label = 'gray';
        endswitch;

        return '<span class="label label-'.$label.'">'.$Status.'</span>';
    }

    /**
     * <b>Obter status de Certificado:</b> Informe o número do status do Certificado para receber sua descrição
     * @param INT $numStatus = número do status no sistema 
     * @param STRING $link = link para download do certificado 
     * @return STRING $Status = valor do status   
     */
    public static function StatusCertificado($numstatus){
        $retorno = '';

        switch($numstatus):
            case '0':
                $retorno = '<span class="label label-primary">Aguardando aprovação</span>';
            break;
            case '1':
                $retorno = '<span class="label label-success">Aprovado</span>';
            break;
            case '2':
                $retorno = '<span class="label label-danger">Cancelado</span>';
            break;
        endswitch;

        return $retorno;
    }

    /**
     * <b>Obter status de Certificado:</b> Informea um código único para um novo certificado
     * @return STRING $codigo = código de Certificado Único   
     */
    public static function GerarCodigoCertificado(){
        $read = new Read;
        $unica = 0;
        $codigo = '';

        while($unica == 0):
            $codigo = self::GeraSenha(8, false, true, false);
            $read->ExeRead('app_certificados', "WHERE certificado_codigo = :codigo", "codigo={$codigo}");
            if ($read->getRowCount() <= 0):
                $unica = 1;
            endif;
        endwhile;

        return $codigo;
    }

    /**
     * <b>Obter tempo relativo:</b> Informe uma data timestamp para receber o tempo relativo de uma ação
     * @param INT $timestamp = tempo em segundos
     * @return STRING $r = retorna o tempo em que determinada ação ocorreu.     
     */
    public static function FormataTempo($timestamp){
        date_default_timezone_set("UTC");
        $difference = time() - $timestamp;

        if($difference >= 60*60*24*365){        // if more than a year ago
            $int = intval($difference / (60*60*24*365));
            $s = ($int > 1) ? 's' : '';
            $r = $int . ' ano' . $s . ' atrás';
        } elseif($difference >= 60*60*24*7*5){  // if more than five weeks ago
            $int = intval($difference / (60*60*24*30));
            $s = ($int > 1) ? ' meses' : ' mês';
            $r = $int . $s . ' atrás';
        } elseif($difference >= 60*60*24*7){        // if more than a week ago
            $int = intval($difference / (60*60*24*7));
            $s = ($int > 1) ? 's' : '';
            $r = $int . ' semana' . $s . ' atrás';
        } elseif($difference >= 60*60*24){      // if more than a day ago
            $int = intval($difference / (60*60*24));
            $s = ($int > 1) ? 's' : '';
            $r = $int . ' dia' . $s . ' atrás';
        } elseif($difference >= 60*60){         // if more than an hour ago
            $int = intval($difference / (60*60));
            $s = ($int > 1) ? 's' : '';
            $r = $int . ' hora' . $s . ' atrás';
        } elseif($difference >= 60){            // if more than a minute ago
            $int = intval($difference / (60));
            $s = ($int > 1) ? 's' : '';
            $r = $int . ' minuto' . $s . ' atrás';
        } else {                                // if less than a minute ago
            $r = 'um momento atrás';
        }

        return $r;
    }

    /**
     * <b>Obter tamanho do arquivo:</b> Informe o tamanho do arquivo em bytes e converterá para um tamanho maior
     * @param INT $tamanho = tamanho do arquivo em bytes
     * @return STRING $t = retorna o tamanho do arquivo com uma unidade maior
     */
    public static function TamanhoArquivo($tamanho){
        if($tamanho > 1000000):
            $t = number_format($tamanho / 1000000, 3, ',', ' ');
            $t = $t.' Mb';
        elseif($tamanho > 1000):
            $t = number_format($tamanho / 1000, 2, '.', ' ');
            $t = $t.' Kb';
        endif;

        return $t;
    }

    /**
     * <b>Cortar String:</b> Informe uma string e o número de espaços (opcional) para receber uma string cortada
     * @param STRING $String = trecho a ser cortado
     * @return STRING $StringCortada = retorna string cortada     
     */
    public static function CortaString($String, $Espacos = 2){
        $pieces = explode(' ', $String);
        $StringCortada = '';

        for($i = 0; $i < $Espacos; $i++):
            if($i < count($pieces)):
                $StringCortada .= $pieces[$i].' ';
            endif;
        endfor;

        return $StringCortada;
    }

    /**
     * <b>Gera Senha:</b> gere uma senha informando alguns parâmetros
     * @param INT $tamanho = tamanho da string
     * @param BOOLEAN $maiusculas = informa se a senha deverá ter letras maiúsculas
     * @param BOOLEAN $numeros = informa se a senha deverá ter números
     * @param BOOLEAN $simbolos = informa se a senha deverá ter símbolos
     * @return STRING $StringCortada = retorna string cortada     
     */
    public static function GeraSenha($tamanho = 8, $maiusculas = true, $numeros = true, $simbolos = false){
        $lmin = 'abcdefghijklmnopqrstuvwxyz';
        $lmai = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = '1234567890';
        $simb = '!@#$%*-';
        $retorno = '';
        $caracteres = '';

        $caracteres .= $lmin;
        if ($maiusculas) $caracteres .= $lmai;
        if ($numeros) $caracteres .= $num;
        if ($simbolos) $caracteres .= $simb;

        $len = strlen($caracteres);
        for ($n = 1; $n <= $tamanho; $n++) {
            $rand = mt_rand(1, $len);
            $retorno .= $caracteres[$rand-1];
        }
        return $retorno;
    }

    /**
     * <b>Verifica chave de array:</b> verifica se a chave do array existe e, caso exista, retorna outra chave
     * @param INT $chave = chave a ser testada
     * @param ARRAY $array = array para teste
     * @return INT $chaveUnica = retorna a chave
     */
    public static function VerificaChaveArray($chave, $array){
        if(array_key_exists($chave, $array)):
            return self::VerificaChaveArray($chave + 1, $array);
        else:
            return $chave;
        endif;
    }

    /**
     * <b>Busca Status Pagos:</b> retorna os status que são pagos
     * @return STRING $statusPagos = retorna os status pagos
     */
    public static function BuscaStatusPagos($array = false){
        if($array == false):
            $statusPagos = '3,4,12,19,22,23,90,91,92,96,97,98,99';
        else:
            $statusPagos = array(3, 4, 12, 19, 22, 23, 90, 91, 92, 96, 97, 98, 99);
        endif;
        return $statusPagos;

        /*
            3 - Paga (PagSeguro)
            4 - Disponnível (PagSeguro)
            12 - Disponível (PayPal)
            19 - Paga (PayPal)
            22 - Paga (PagHiper)
            23 - Disponível (PagHiper)
            90 - Professor
            96 - Dinheiro
            97 - Depósito
            98 - Boleto
        */
    }

    /**
    * <b>Calcula Juros Simples:</b> calcula juros simples sem atualização financeira
    * @return FLOAT $montante = valor após aplicação dos juros
    */
    public static function calculaJurosSimples($parcela, $diasAtraso, $taxaJuros = 0.01, $taxaMulta = 0.02){
        $multa =  $parcela * $taxaMulta;
        $juros = $parcela * $taxaJuros * $diasAtraso / 30;
        $montante = $parcela + $multa + $juros;
        return $montante;
    }

    /**
    * <b>Calcula Juros Compostos:</b> calcula juros compostos sem atualização financeira
    * @return FLOAT $montante = valor após aplicação dos juros
    */
    public static function calculaJurosCompostos($parcela, $diasAtraso, $taxaJuros = 0.01, $taxaMulta = 0.02){
        $multa = $parcela * $taxaMulta;
        $juros = $parcela * pow(1 + $taxaJuros, $diasAtraso / 30) - $parcela;
        $montante = $parcela + $multa + $juros;
        return $montante;
    }

    /**
    * <b>Calcula Juros Compostos Com Atualização:</b> calcula juros compostos com atualização financeira
    * @return FLOAT $montante = valor após aplicação dos juros
    */
    public static function calculaJurosCompostosComAtualizacao($parcela, $diasAtraso, $taxaAtualizacao, $taxaJuros = 0.01, $taxaMulta = 0.02){
        $percentualDoMes = $diasAtraso / 30;
        $multa = $parcela * $taxaMulta;
        $juros = $parcela * pow(1 + $taxaJuros, $percentualDoMes) - $parcela;   
        $atualizacao = $parcela * pow(1 + $taxaAtualizacao, $percentualDoMes) - $parcela;
        $montante = $parcela + $juros + $multa + $atualizacao;
        return $montante;
    }

    /**
    *<b>Gera UUID:</b> gera UUID
    * @return STRING $uuid = uuid criado
    */
    public static function geraUUID(){
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
    *<b>sendMessageFirebase:</b> Envia mensagem para firebase
    */
    function sendMessageFirebase($data, $target, $notification){
        //FCM api URL
        $url = 'https://fcm.googleapis.com/fcm/send';
        //api_key available in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
        $server_key = 'AAAAcVzQLjQ:APA91bExsVG2Nd2DDKg7kvghb3CGnDG6P8Sv4RuKbQ_60SFOZQpaY8G4K_InXu0u6D93zfq-rPb8y9OvCBRPW3wofM_AZYwrX70wANtifLm9WzESNYdRAZQny_aJcuWESQ4L2GfmGyt1';
                    
        $fields = array();
        $fields['data'] = $data;

        $fields['priority'] = "normal";
        $fields['notification'] = array("title" => $notification['title'], "body" => $notification['body']);

        if(is_array($target)){
            $fields['registration_ids'] = $target;
        }else{
            $fields['to'] = $target;
        }

        //header with content_type api key
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key='.$server_key
        );
                    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        if ($result === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}