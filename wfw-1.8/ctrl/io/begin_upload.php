<?php
/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2010-2011,2013 Thomas AUGUEY <contact@aceteam.org>
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

/*
 * Prépare l'upload d'un fichier
 * Rôle : Visiteur
 * UC   : begin_upload
 */

class Ctrl extends cApplicationCtrl{
    public $fields    = array('file_size', 'filename', 'content_type');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        $mode = $app->getCfgValue("io_module","storage_mode");

        // 1. Vérifie la taille maximum alloué à l'upload
        $max_size = $app->getCfgValue("io_module","max_upload_size");
        if($p->file_size > $max_size)
            return RESULT(cResult::Failed, "IO_FILE_TO_BIG");
        if($p->file_size < 1)
            return RESULT(cResult::Failed, "IO_ZERO_FILE_SIZE");

        // 2. Crée le dossier d'upload si besoin
        $upload_dir = $app->getCfgValue("io_module","upload_dir");
        if($mode=="file" && !file_exists($upload_dir))
            return RESULT(cResult::Failed, "IO_UPLOAD_DIR_NOT_EXISTS");
        //if(!file_exists($upload_dir) && (cmd("mkdir ".$upload_dir,$cmd_out)!=0))
        //    return RESULT(cResult::Failed, "IO_CANT_CREATE_UPLOAD_DIR");

        // 3. Initialise l'entree en BDD
        $output_dir = $app->getCfgValue("io_module","public_output_dir");
        if(!$app->callStoredProc('io_create_upload',
            $p->file_size,
            $p->filename,
            $output_dir,
            ($mode=="file") ? ($upload_dir) : NULL,
            $_SERVER["REMOTE_ADDR"],
            $p->content_type
        )) return false;
        $result = cResult::getLast();
        //if(!IoUploadMgr::getById($uploadInst,cResult::getLast()->getAtt("IO_UPLOAD_ID")))
        //    return false;

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
};


?>