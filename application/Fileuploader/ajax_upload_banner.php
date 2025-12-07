<?php
	include('../../config/db.php');
	include('class.fileuploader.php');
	require 'vendor/autoload.php';

	$isAfterEditing = false;

	$sql_imagem = $db->prepare("SELECT * FROM banners WHERE nome = '".$_POST['_namee']."'");
	$sql_imagem->execute();
	$imagem = $sql_imagem->fetch(PDO::FETCH_ASSOC);

	if (!file_exists('../../uploads/banners/')) {
		mkdir('../../uploads/banners/', 0777, true);
	}

	$caminho = '../../uploads/banners/';

	if (isset($_POST['fileuploader']) && isset($_POST['_editingg'])) {
		$isAfterEditing = true;
		$nome     		= $_POST['_namee'];
	}else{
		$extensao 		= pathinfo($_POST['_namee'], PATHINFO_EXTENSION);
		$nome     		= md5(microtime($_POST['_namee'])).'.jpg';
	}

	$FileUploader = new FileUploader('files', array(
		'limit' => null,
		'maxSize' => null,
		'fileMaxSize' => null,
		'extensions' => null,
		'required' => false,
		'uploadDir' => $caminho,
		'title' => ''.$nome.'',
		'replace' => $isAfterEditing,
		'editor' => array(
            'maxWidth' => 1024,
            'maxHeight' => 410,
            'crop' => true,
            'quality' => null
		),
		'listInput' => true,
		'files' => null
	));

	if (isset($_POST['fileuploader']) && isset($_POST['_editingg'])) {}else{

		$foto_completa	= DOMAIN.'/uploads/banners/'.strtolower($nome);
		$dados = array($foto_completa, $nome, $_POST['_namee']);
		$sql = $db->prepare("INSERT INTO banners (imagem, nome, original) VALUES (?,?,?)");
		$sql->execute($dados);

	}

	$upload = $FileUploader->upload();

	echo json_encode($upload);

	exit;