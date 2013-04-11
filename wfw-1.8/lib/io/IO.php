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


require_once("php/class/bases/iModule.php");
require_once("php/class/bases/socket.php");
require_once("php/xml_default.php");

    
class IOModule implements iModule
{
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
}

?>
