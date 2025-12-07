<?php
	include('../../config/db.php');
	include('class.fileuploader.php');
	require 'vendor/autoload.php';

	$isAfterEditing = false;

	$mes = date('m');
	$ano = date('Y');

	if (!file_exists('../../../../cms/produtos/'.$ano.'/'.$mes.'/')) {
		mkdir('../../../../cms/produtos/'.$ano.'/'.$mes.'/', 0777, true);
	}

	$caminho = '../../../../cms/produtos/'.$ano.'/'.$mes.'/';

	if (isset($_POST['fileuploader']) && isset($_POST['_editingg'])) {
		$isAfterEditing = true;
		$nome     		= $_POST['_namee'];
	}else{
		$extensao 		= pathinfo($_POST['_namee'], PATHINFO_EXTENSION);
		$nome     		= md5(microtime($_POST['_namee'])).'.jpg';
	}


	$codigo = substr($_POST['_namee'],0,-4);

	$cod = explode('_', $codigo);
	$codigo_produto = $cod[0];

	$dados_produto = array($_GET['empresa_id'], $codigo_produto);
	$sql_produto = $db->prepare("SELECT * FROM produtos WHERE empresa = ? AND codigo = ? LIMIT 1");
	$sql_produto->execute($dados_produto);

	if($sql_produto->rowCount() == 0){
		$dados_produto = array($_GET['empresa_id'], $codigo_produto);
		$sql_produto = $db->prepare("SELECT * FROM produtos WHERE empresa = ? AND reforiginal = ? LIMIT 1");
		$sql_produto->execute($dados_produto);
	}

	$produto = $sql_produto->fetch(PDO::FETCH_ASSOC);

	if(!empty($produto['id'])){

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
	            'maxWidth' => 1000,
	            'maxHeight' => 1000,
	            'crop' => true,
	            'quality' => null
			),
			'listInput' => true,
			'files' => null
		));

		if (isset($_POST['fileuploader']) && isset($_POST['_editingg'])) {}else{

			$foto_completa	= 'https://'.$_SERVER['SERVER_NAME'].'/cms/produtos/'.$ano.'/'.$mes.'/'.strtolower($nome);
			$dados = array($produto['id'], $foto_completa, $nome, $_POST['_namee']);
			$sql = $db->prepare("INSERT INTO produtos_imagens (id_produto, imagem, nome, original) VALUES (?,?,?,?)");
			$sql->execute($dados);

		}

		$upload = $FileUploader->upload();

		if($upload){
		
				//THUMBNAIL
				$sql_imagens = $db->prepare("SELECT * FROM produtos_imagens ORDER BY id DESC LIMIT 1");
				$sql_imagens->execute();
				$dados_imagens = $sql_imagens->fetch(PDO::FETCH_ASSOC);

				$dados_imagens['imagem'] = str_replace("https://".$_SERVER['SERVER_NAME']."/","../../../../",$dados_imagens['imagem']);
				$nome = explode('.', $dados_imagens['nome']);

				if (!file_exists('../../../../cms/produtos_thumbnail/'.$ano.'/'.$mes.'/')) {
					mkdir('../../../../cms/produtos_thumbnail/'.$ano.'/'.$mes.'/', 0777, true);
				}

				// create a new instance of the class
				$image = new Zebra_Image();
				$image->auto_handle_exif_orientation = false;

				$image->source_path = '../../../../cms/produtos/'.$ano.'/'.$mes.'/'.strtolower($dados_imagens['nome']);

				$image->target_path = '../../../../cms/produtos_thumbnail/'.$ano.'/'.$mes.'/'.$nome[0].'.jpg';

				$imagem_thumbnail = 'https://'.$_SERVER['SERVER_NAME'].'/'.substr($image->target_path, 12);
				$sql_update = $db->prepare("UPDATE `produtos_imagens` SET `thumbnail` = '".$imagem_thumbnail."' WHERE `id` = '".$dados_imagens['id']."'");
				$sql_update->execute();

				$image->jpeg_quality = 20;
				$image->preserve_aspect_ratio = true;
				$image->enlarge_smaller_images = true;
				$image->preserve_time = true;
				$image->handle_exif_orientation_tag = true;

				// resize the image to exactly 100x100 pixels by using the "crop from center" method
				if (!$image->resize(400, 400, ZEBRA_IMAGE_CROP_CENTER)) {};
			
		}

		echo json_encode($upload);

	}else{
		echo '{"hasWarnings":false,"isSuccess":true,"warnings":[],"files":[{"date":"Wed, 09 Oct 2019 13:29:16 -0300","editor":true,"extension":"jpg","file":"..\/..\/..\/..\/cms\/produtos\/ampri\/2019\/10\/662ae75fb853f3d9ec2b3c7e5f8e64f5.jpg","name":"662ae75fb853f3d9ec2b3c7e5f8e64f5.jpg","old_name":"9105_B.JPG","old_title":"9105_B","replaced":false,"size":22635,"size2":"22.10 KB","title":"662ae75fb853f3d9ec2b3c7e5f8e64f5","type":"image\/jpeg","uploaded":true}]}';
	}


	exit;
