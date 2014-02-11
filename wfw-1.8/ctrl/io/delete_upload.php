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
 * Supprime le téléchargement d'un fichier
 * Rôle : Visiteur
 * UC   : delete_upload
 */
class io_module_delete_upload_ctrl extends cApplicationCtrl{
    public $fields    = array('io_upload_id');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        // 1. Supprime de la BDD
        if(!$app->callStoredProc('io_delete_upload',
            $p->io_upload_id
        )) return false;
        $result = cResult::getLast();
        
        //OK
        return RESULT_INST($result);
    }
};


?>