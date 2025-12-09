<?php
	@session_start();
	try {
		$db = new PDO('mysql:host=177.234.145.178;dbname=rafael_tanamesa', 'rafael_tanamesa', 'm7GUx7X639AOUhGlDV');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
		if($e->getCode() == 1049){
			echo "Banco de dados errado.";
		}else{
			echo $e->getMessage();
		}
	}

    define('DOMAIN', 'https://fast4.com.br');
    define('PATH', 'https://fast4.com.br');
    define('NAME', 'Pericia');
    define('PRODUCTION', false);