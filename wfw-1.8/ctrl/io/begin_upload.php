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

class io_module_begin_upload_ctrl extends cApplicationCtrl{
    public $fields    = array('file_size', 'filename', 'content_type');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        $mode = $app->getCfgValue("io_module","storage_mode");

        // 1. Vérifie la taille minimum/maximum allouée à l'upload
        $max_size = $app->getCfgValue("io_module","max_upload_size");
        if($p->file_size > $max_size)
            return RESULT(cResult::Failed, "IO_FILE_TO_BIG");
        if($p->file_size < 1)
            return RESULT(cResult::Failed, "IO_ZERO_FILE_SIZE");

        // 2. Initialise l'entree en BDD
        if(!$app->callStoredProc('io_create_upload',
            $p->file_size,
            $p->filename,
            $_SERVER["REMOTE_ADDR"],
            $p->content_type
        )) return false;
        $result = cResult::getLast();
        //if(!IoUploadMgr::getById($uploadInst,cResult::getLast()->getAtt("IO_UPLOAD_ID")))
        //    return false;

        //OK
        return RESULT_INST($result);
    }
};


?>