<?php

/*
	(C)2010-2011 ID-INFORMATIK. WebFrameWork(R)
  Debute le stockage d'un fichier

  Arguments:
		[Name]       wfw_id     : Identificateur du dossier à vérfier
		[Password]     [wfw_pwd]  : Optionnel, mot de passe du dossier  
    size      : taille du fichier
    
  Retourne:         
		token      : jeton de l'upload
		result     : Résultat de la requête
		info       : Détails sur l'erreur en cas d'echec
	
  Remarques:
	beginUpload crer un fichier temporaire de la taille demandé dans le dossier.
	Les fragments de fichiers sont ensuite envoyés à la requête packetUpload qui va écrire le fichier.

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
	array('wfw_id'=>'cInputName','size'=>'cInputInteger'),
	//optionnels
	array('wfw_pwd'=>'cInputPassword')
	);

//
//globales
//     
$id  = $_REQUEST["wfw_id"];
$pwd = isset($_REQUEST["wfw_pwd"])?$_REQUEST["wfw_pwd"]:"";    
$file_name = CLIENT_DATA_PATH."/$id.xml";
$file_dir = CLIENT_DATA_PATH."/$id/";
$size  = intval($_REQUEST["size"]);
$token  = uniqid();
$upload_file_name  = upload_path($token);

//
// charge le fichier xml
//     
$doc = clientOpen($id);

//
// vérifie le mot de passe
//
clientCheckPassword($doc,$pwd);

//
// limitation du dossier
//   
$max_size = clientGetMaxFileSize($doc);
$max_file = clientGetMaxFile($doc);

if($size>$max_size)
	rpost_result(ERR_FAILED, "file_too_big");

//
// cree le dossier d'upload si besoin
//
if(!file_exists($file_dir) && (cmd("mkdir ".$file_dir,$cmd_out)!=0))
	rpost_result(ERR_SYSTEM, "cant_create_folder");

//
// vérifie le nombre de fichiers présents 
//    
$file_count = clientGetFileCount($id);
if($file_count>=$max_file) 
	rpost_result(ERR_FAILED, "max_file_count");

//
// cree le fichier dummy
//

if($fp = fopen($upload_file_name, "wb"))
{
	fseek($fp, $size-1, SEEK_SET);
	fwrite($fp, 0xFF, 1);//dernier block
	fclose($fp);
}
else
	rpost_result(ERR_FAILED, "file_create");

//termine
rpost("id",$id);
rpost("token",$token);
rpost("size",$size);
rpost_result(ERR_OK);
?>
