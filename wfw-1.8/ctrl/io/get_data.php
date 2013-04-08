<?php
/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2013 Thomas AUGUEY <contact@aceteam.org>
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
 * Affiche les données d'un fichier uploadé dans la sortie
 * Rôle : Administrateur
 * UC   : get_data
 */

class Ctrl extends cApplicationCtrl{
    public $fields    = array('io_upload_id');
    public $op_fields = array('encoded_output');

    function main(iApplication $app, $app_path, $p)
    {
        if(!IoUploadMgr::getById($upload, $p->io_upload_id))
            return false;
        
        $result = cResult::getLast();
        
        //decode les paquets
        $data=NULL;
        if(!IOModule::getData($upload,$data))
            return false;
        
        //sortie
        header("Content-Type:".$upload->contentType);
        header('Content-Description: '.$upload->filename);
        header('Content-Disposition: inline; filename="'.$upload->filename.'"');
        echo($data);
        exit;
    }
};


?>