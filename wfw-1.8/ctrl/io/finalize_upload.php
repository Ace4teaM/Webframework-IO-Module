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
 * Finalise un upload
 * Rôle : Visiteur
 * UC   : finalize_upload
 */

class io_module_finalize_upload_ctrl extends cApplicationCtrl{
    public $fields    = array('token', 'filename');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        $upload_dir = $app->getCfgValue("io_module","upload_dir");

        //
        // Vérifie si le fichier d'upload existe
        //
        $upload_file_name  = $upload_dir."/".$token;
        if(!file_exists($upload_file_name))
            return RESULT(cResult::Failed, "IO_INVALID_UPLOAD_TOKEN");

        //
        // Renomme le fichier
        //
        rename($upload_file_name,$output_dir."/".$p->filename);

        return RESULT_OK();
    }
};


?>