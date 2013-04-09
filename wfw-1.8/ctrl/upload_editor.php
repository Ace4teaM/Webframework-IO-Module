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
 * Editeur d'upload
 * Rôle : Administrateur
 * UC   : .
 */

class Ctrl extends cApplicationCtrl{
    public $fields    = null;
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        return RESULT_OK();
    }
    
    function output(iApplication $app, $format, $att, $result)
    {
        if(!$result->isOK())
            return parent::output($app, $format, $att, $result);
        
        switch($format){
            case "html":
                return $app->MakeXMLView("view/io/pages/upload_editor.html",array());
        }
        return parent::output($app, $format, $att, $result);
    }
};

?>