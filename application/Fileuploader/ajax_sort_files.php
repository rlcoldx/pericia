<?php
	include('../../config/db.php');

    $list = isset($_POST['_list']) ? json_decode($_POST['_list'], true) : null;
   
	foreach ($list as $key => $item){

		$sql = $db->prepare("UPDATE `restaurantes_imagens` SET `order` = '".$item['index']."' WHERE `id_restaurante` = '".$_GET['id_restaurante']."' AND nome = '".$item['name']."'");
		$sql->execute();
		
	}

	echo '1';