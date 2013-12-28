<?php
/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2012-2013 Thomas AUGUEY <contact@aceteam.org>
    ---------------------------------------------------------------------------------------------------------------------------------------
    This file is part of WebFrameWork.

    WebFrameWork is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WebFrameWork is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WebFrameWork.  If not, see <http://www.gnu.org/licenses/>.
    ---------------------------------------------------------------------------------------------------------------------------------------
*/

/**
 * Gestionnaire de courriers électroniques
 * Librairie PHP5
 */


require_once("class/bases/iModule.php");
require_once("class/bases/socket.php");
require_once("xml_default.php");

    
class IOModule implements iModule
{
    //Repository
    const RepositoryAlreadyExists  = "IO_REPOSITORY_ALREADY_EXISTS";
    const CantLinkEvent            = "IO_CANT_LINK_EVENT";
    const RepositoryNotExists      = "IO_REPOSITORY_NOT_EXISTS";
    const InvalidPassword          = "IO_REPOSITORY_INVALID_PWD";
    const RepositoryPathNotExists  = "IO_REPOSITORY_PATH_NOT_EXISTS";
    
    /**
     * @brief Initialise le module
     * @param $local_path Chemin d'accès local vers ce dossier
     */
    public static function load($local_path){
        global $app;
        
        //chemins d'acces 
        //$this_path = dirname(__FILE__);
        //$this_relative_path = relativePath($this_path,$local_path);
        
        //print_r($this_path);
        
        //initialise la configuration
        $modParam = parse_ini_file("$local_path/config.ini", true);
        $app->config = array_merge_recursive($modParam,$app->config);

        //inclue le model de données
        require_path($local_path."/".$app->getCfgValue("io_module","lib_path"));
    }
    
    public static function libPath(){
        global $app;
        return $app->getLibPath("io_mod").$app->getCfgValue("io_module","lib_path");
    }
    
    public static function makeView($name,$attributes,$template_file){ 
    }
    
    /**
     * @brief Obtient les données décodées d'un fichier uploadé
     * @param $upload Instance ou identifiant de l'objet Upload (IoUpload)
     * @param $data Pointeur recevant les données
     * @return Résultat de procédures
     */
    public static function getData($upload,&$data)
    {
        //identifiant de l'item
        $id = $upload instanceof IoUpload ? $upload->ioUploadId : $upload;

        //prepare la requete
        if(!IoPacketMgr::getAll($list, "io_upload_id = '$id' order by packet_num"))
            return false;
        
        $data="";
        foreach($list as $key=>$inst){
            $data .= base64_decode($inst->base64Data);
        }

        return RESULT_OK();
    }
    
    /**
     * @brief Obtient les données décodées d'un fichier uploadé
     * @param $upload Instance ou identifiant de l'objet Upload (IoUpload)
     * @param $data Pointeur recevant les données
     * @return Résultat de procédures
     */
    function makeUpload($filename, $file_size, $content_type, $output_dir, $upload_dir, $ip)
    {
        $mode = $app->getCfgValue("io_module","storage_mode");

        // Vérifie la taille maximum alloué à l'upload
        $max_size = $app->getCfgValue("io_module","max_upload_size");
        if($file_size > $max_size)
            return RESULT(cResult::Failed, "IO_FILE_TO_BIG");
        if($file_size < 1)
            return RESULT(cResult::Failed, "IO_ZERO_FILE_SIZE");

        // Crée le dossier d'upload si besoin
        if($upload_dir == null)
            $upload_dir = $app->getCfgValue("io_module","upload_dir");
        if($mode=="file" && !file_exists($upload_dir))
            return RESULT(cResult::Failed, "IO_UPLOAD_DIR_NOT_EXISTS");

        // Initialise l'entree en BDD
        $output_dir = $app->getCfgValue("io_module","public_output_dir");
        if(!$app->callStoredProc('io_create_upload',
            $file_size,
            $filename,
            $output_dir,
            ($mode=="file") ? ($upload_dir) : NULL,
            $ip,
            $content_type
        )) return false;
        $result = cResult::getLast();

        // 4. Prepare l'espace de stockage
        $io_upload_id = $result->getAtt("IO_UPLOAD_ID");
        $packet_size  = intval($result->getAtt("PACKET_SIZE")); //$app->getCfgValue("io_module","packet_size");
        $packet_count = intval($result->getAtt("PACKET_COUNT")); // intval(ceil($p->file_size / $packet_size));
        if($mode == "file"){
            // 3a. Crée un fichier dummy qui va recevoir les données
            $upload_file_name  = $upload_dir."/".$io_upload_id;
            if($fp = fopen($upload_file_name, "wb"))
            {
                fseek($fp, $p->file_size-1, SEEK_SET);
                fwrite($fp, 0xFF, 1);//dernier block
                fclose($fp);
            }
            else
                return RESULT(cResult::Failed, "IO_DUMMY_UPLOAD_FILE_CREATE");
        }
        else{
            // 3a. Initialise les entrees en base de données
            // (aucune action necessaire)
        }
        
        //OK
        return RESULT_INST($result);
    }
    
    /**
     * @brief Fabrique un dossier de dêpot
     * @param $upload Instance ou identifiant de l'objet Upload (IoUpload)
     * @param $data Pointeur recevant les données
     * @return Résultat de procédures
     */
    public static function getRepository($repository_id, &$doc, &$infos)
    {
        if(!self::getRepositoryInfos($repository_id,$infos))
            return false;
        
        $doc = new XMLDocument("1.0", "utf-8");
        return $doc->load($infos["file_path"]);
    }
    
    
    /**
     * @brief Supprime un dêpot
     * @param $repository_id Identifiant du dêpot à supprimé
     * @return Résultat de procédures
     */
    public static function removeRepository($repository_id)
    {
        if(!self::getRepositoryInfos($repository_id,$infos))
            return false;
        
        if(file_exists($infos["file_path"]) && !unlink($infos["file_path"]))
            return RESULT(cResult::System,cApplication::CantRemoveResource,error_get_last());
        
        if(file_exists($infos["data_path"]) && !rrmdir($infos["data_path"]))
            return false;//rrmdir result
        
        return RESULT_OK();
    }
    
    /**
     * @brief Fabrique un dossier de dêpot
     * @param $upload Instance ou identifiant de l'objet Upload (IoUpload)
     * @param $data Pointeur recevant les données
     * @return Résultat de procédures
     */
    public static function getRepositoryFieldsDoc($repository_id, &$doc)
    {
        if(!self::getRepositoryInfos($repository_id,$infos))
            return false;
        
        $fields_doc = new XMLDocument("1.0", "utf-8");
        $fields_doc->load($infos["file_path"]);
        
        $doc = $fields_doc;
        
        return RESULT_OK();
    }
    
    /**
     * @brief Fabrique un dossier de dêpot
     * @param $upload Instance ou identifiant de l'objet Upload (IoUpload)
     * @param $data Pointeur recevant les données
     * @return Résultat de procédures
     */
    public static function getRepositoryFields($repository_id, &$array)
    {
        if(!self::getRepositoryFieldsDoc($repository_id,$fields_doc))
            return false;
        
        $array=$fields_doc->toArray();
        return RESULT_OK();
    }
    
    /**
     * @brief Obtient les informations sur un dêpot
     * @param $upload Identifiant du dêpot
     * @param $infos Pointeur recevant les données
     * @return Résultat de procédures
     */
    public static function getRepositoryInfos($repository_id,&$infos)
    {
        global $app;
        $file_path = $app->getCfgValue("io_module","repository_data_path")."/$repository_id.xml";
        $data_path = $app->getCfgValue("io_module","repository_data_path")."/$repository_id";

        if(!file_exists($file_path))
            return RESULT(cResult::Failed, IOModule::RepositoryNotExists);

        $infos = array( 'file_path'=>$file_path, 'data_path'=>$data_path );

        return RESULT_OK();
    }
    
    /**
     * Tronque une image en miniature
     * @param type $image    Image source
     * @param type $size     Hauteur/Largeur désirée de la destination
     * @param type $src_w    Largeur utilisé dans l'image source
     * @param type $src_h    Hauteur utilisé dans l'image source
     * @param type $src_x    Offset X utilisé dans l'image source
     * @param type $src_y    Offset Y utilisé dans l'image source
     * @return Résultat de procédure
     * @retval resource Identifiant de ressource de la nouvelle image
     */
    public static function truncateImageThumb($image,$size,$src_w,$src_h,$src_x,$src_y){ 
        //obtient les dimentions de l'image
        $org_w = imagesx($image);
        $org_h = imagesy($image);

        //
        // Calcule la taille de destination (en pixels)
        //
        if(!$src_w||!$src_h){
            $src_w = ($org_w>$org_h)?$org_h:$org_w;
            $src_h = $src_w;
        }
        if ($src_h > $src_w) {
            $dst_w = intval(($size / $src_h) * $src_w);
            $dst_h = $size;
        } else {
            $dst_w = $size;
            $dst_h = intval(($size / $src_w) * $src_h);
        }

        if (!$dst_w || !$dst_h)
            return RESULT(cResult::Failed, "IO_INVALID_DST_SIZE");

        //
        // Crée la nouvelle image
        //
        $new_image = imagecreatetruecolor($dst_w, $dst_h);
        if (!$new_image)
            return RESULT(cResult::Failed, "IO_CREATE_IMAGE");
        imagealphablending($new_image, true);
        imagesavealpha($new_image, true);

        //copie dans la nouvelle image
        imagecopyresampled($new_image, $image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        RESULT_OK();
        return $new_image;
    }
    
    /**
     * Tronque la taille d'une image 
     * @param type $image    Image source
     * @param type $size     Hauteur/Largeur maximale désirée pour la destination
     * @return Résultat de procédure
     * @retval resource Identifiant de ressource de la nouvelle image
     */
    public static function truncateImage($image,$size){ 
        //obtient les dimentions de l'image
        $src_w = imagesx($image);
        $src_h = imagesy($image);

        //
        // Calcule la taille de destination (en pixels)
        //
        if ($src_h > $src_w) {
            $dst_w = intval(($size / $src_h) * $src_w);
            $dst_h = $size;
        } else {
            $dst_w = $size;
            $dst_h = intval(($size / $src_w) * $src_h);
        }

        if (!$dst_w || !$dst_h)
            return RESULT(cResult::Failed, "IO_INVALID_DST_SIZE");

        //
        // Crée la nouvelle image
        //
        $new_image = imagecreatetruecolor($dst_w, $dst_h);
        if (!$new_image)
            return RESULT(cResult::Failed, "IO_CREATE_IMAGE");
        imagealphablending($new_image, true);
        imagesavealpha($new_image, true);

        //copie dans la nouvelle image
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

        RESULT_OK();
        return $new_image;
    }
    
}

?>
