<?php

/*
	(C)2010-2011 ID-INFORMATIK. WebFrameWork(R)
	Continue le stockage d'un fichier

  Arguments:
    [Integer]      size      : Taille du paquet
    [Integer]      offset    : Offset du paquet
    [UNIXFileName] token     : Jeton de l'upload
    [data]         data      : Données
    [Identifier]   encoded   : Type d'encodage des données (base64)
    
  Retourne:         
    result     : resultat de la requete.
    info       : details sur l'erreur en cas d'echec.
	
  Revisions:
	[29-12-2011] Implentation
		[09-01-2012] Update
*/

define("THIS_PATH", dirname(__FILE__)); //chemin absolue vers ce script
define("ROOT_PATH", realpath(THIS_PATH."/../../")); //racine du site
include(ROOT_PATH.'/wfw/php/base.php');
include_path(ROOT_PATH.'/wfw/php/');
include_path(ROOT_PATH.'/wfw/php/class/bases/');
include_path(ROOT_PATH.'/wfw/php/inputs/');

include(ROOT_PATH.'/req/client/path.inc');
include(ROOT_PATH.'/req/client/client.inc');


//
// Prepare la requete pour repondre à un formulaire
//

useFormRequest();                         

//
//verifie les champs obligatoires
//
rcheck(
	//requis
	array('token'=>'cInputUNIXFileName','offset'=>'cInputInteger','size'=>'cInputInteger','data'=>''),
	//optionnels
	array('encoded'=>'cInputIdentifier')
	);

//
//globales
//     
$id  = $_REQUEST["wfw_id"];
$file_name = CLIENT_DATA_PATH."/$id.xml";
$file_dir = CLIENT_DATA_PATH."/$id/";
$size  = intval($_REQUEST["size"]);
$offset= intval($_REQUEST["offset"]);
$token  = $_REQUEST["token"];
$data  = $_REQUEST["data"];
$upload_file_name  = upload_path($token);

//decode les données ?
if(isset($_REQUEST["encoded"]) && ($_REQUEST["encoded"]=="base64"))
{
	$data=base64_decode($_REQUEST["data"]);
}

//verifie la taille des données
if(strlen($data) != $size)
{
	rpost_result(ERR_FAILED, "invalid_size");
}

//
// obtient le fichier d'upload temporaire
//
if(!file_exists($upload_file_name)) 
	rpost_result(ERR_FAILED, "file_not_found");

//
// ecrit les donnees
//
if($fp = fopen($upload_file_name, "c"))
{
	// verifie les limites
	$fstat = fstat($fp);
	$fp_size = intval($fstat['size']);
	//print_r($fstat); exit;
	if($offset+$size > $fp_size)
	{
		fclose($fp);
		rpost_result(ERR_FAILED, "file_overflow");
	}
	// deplace a l'offset
	if(fseek($fp,$offset,SEEK_SET)!=0)
	{
		fclose($fp);
		rpost_result(ERR_FAILED, "seek_error");
	}
	// ecrit les données
	if(!fwrite($fp, $data))
	//if(fwrite($fp, $data, $size) != $size)
	{
		fclose($fp);
		rpost_result(ERR_FAILED, "write_error");
	}
	fclose($fp);
}
else
	rpost_result(ERR_FAILED, "file_open");

//termine
rpost("id",$id);
rpost("offset",$offset);
rpost("token",$token);
rpost_result(ERR_OK);
?>
