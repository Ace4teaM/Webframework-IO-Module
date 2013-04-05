<?php

/*
	(C)2010-2011 ID-INFORMATIK. WebFrameWork(R)
	Continue le stockage d'un fichier

	Arguments:
		[Name]         wfw_id    : Identificateur
		[Password]     [wfw_pwd] : Optionnel, Mot de passe
		[UNIXFileName] token     : Jeton de l'upload
		[UNIXFileName] filename  : Nom du fichier désiré
		[]             [action]  : Optionnel, Action à effectuer sur le fichier de destination (not_exists, new, replace). Par défaut "replace"

	Retourne:         
		id         : Identificateur du dossier
		filename   : Nom du fichier uploadé
		result     : resultat de la requete.
		info       : details sur l'erreur en cas d'echec.
	
	Revisions:
		[04-01-2012] Implentation
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
	array('wfw_id'=>'cInputName','token'=>'cInputUNIXFileName','filename'=>'cInputUNIXFileName'),
	//optionnels
	array('wfw_pwd'=>'cInputPassword','action'=>'')
);

//
//globales
//     
$id  = $_REQUEST["wfw_id"];
$pwd = isset($_REQUEST["wfw_pwd"])?$_REQUEST["wfw_pwd"]:"";    
$file_name = CLIENT_DATA_PATH."/$id.xml";
$file_dir = CLIENT_DATA_PATH."/$id/";
$token  = $_REQUEST["token"];
$filename = $_REQUEST["filename"];
$upload_file_name  = upload_path($token);
$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : "replace";  

//
// charge le fichier xml
//     
$doc = clientOpen($id);

//
// vérifie le mot de passe
//
clientCheckPassword($doc,$pwd);

//
// action à appliquer au fichier
//
switch($action)
{
	//si le fichier n'existe pas
	case "not_exists":
		if(file_exists($file_dir.$filename)) 
			rpost_result(ERR_FAILED, "output_file_exist");
		break;
	//nouveau fichier
	case "new":
		$filename = uniq_filename($filename,$file_dir);
		break;
	//remplace le fichier existant
	case "replace":
	default:
		break;
}

//
// le fichier d'upload existe ?
//
if(!file_exists($upload_file_name)) 
	rpost_result(ERR_FAILED, "file_not_found");

//
// cree le dossier d'upload si besoin
//
if(!file_exists($file_dir) && (cmd("mkdir ".$file_dir,$cmd_out)!=0))
	rpost_result(ERR_SYSTEM, "cant_create_folder");

//
// vérifie le nombre de fichiers présents 
//    
$max_file = clientGetMaxFile($doc);
$file_count = clientGetFileCount($id);
if($file_count>=$max_file) 
	rpost_result(ERR_FAILED, "max_file_count");

//
// renomme le fichier
//
rename($upload_file_name,$file_dir.$filename);

//termine
rpost("id",$id);
rpost("token",$token);
rpost("filename",$filename);
rpost_result(ERR_OK);
?>
