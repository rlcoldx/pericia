<?php
    // Legacy / scripts isolados. A app principal usa config/config.php (composer autoload).
    require_once __DIR__ . '/app_url.php';

    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/bootstrap_session.php';
    }

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

    // Não sobrescreve DOMAIN/PATH se config.php já definiu
    if (!defined('DOMAIN')) {
        if (pericia_is_local_host()) {
            $baseUrl = pericia_detect_base_url();
            define('DOMAIN', $baseUrl);
            define('PATH', $baseUrl);
            define('PRODUCTION', false);
        } else {
            define('DOMAIN', 'https://fast4.com.br');
            define('PATH', 'https://fast4.com.br');
            define('PRODUCTION', true);
        }
    }
    if (!defined('NAME')) {
        define('NAME', 'Pericia');
    }
