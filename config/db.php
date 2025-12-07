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

    define('DOMAIN', 'http://localhost/pericia');
    define('PATH', 'http://localhost/pericia');
    define('NAME', 'Pericia');
    define('PRODUCTION', false);