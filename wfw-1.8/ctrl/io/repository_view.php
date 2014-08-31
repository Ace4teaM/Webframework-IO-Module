<?php
/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2010-2012,2013 Thomas AUGUEY <contact@aceteam.org>
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
 * Affiche les champs d'un dêpot
 * Rôle : Visiteur
 * UC   : repository_view
 */

class io_module_repository_view_ctrl extends cApplicationCtrl{
    public $fields    = array('repository_id');
    public $op_fields = array('repository_pwd');
    
    public $att = array();
    
    function main(iApplication $app, $app_path, $p)
    {
        //
        // 3. Vérifie le mot-de-passe
        //
        /*$pwdNode = $doc->one("repository_pwd",$doc->documentElement);
        if($pwdNode && !empty($pwdNode->nodeValue)){
            if($pwdNode->nodeValue != $p->repository_pwd)
                return RESULT(cResult::Failed, IoModule::InvalidPassword);
        }*/
        
        //liste les données
        $result = null;
        if($db->execute("select name, value from io_repository_entry where io_repository_id=".$db->parseValue($p->io_repository_id),$result)){
            for($i=0; $i<$result->rowCount(); $i++){
                if(!$db->seek($i))
                    continue;
                $this->att[$result->fetchValue('name')] = $result->fetchValue('value');
            }
        }
        
        return RESULT(cResult::Ok,cResult::Success,array("repository_id"=>$p->repository_id));
    }

    function output(iApplication $app, $format, $att, $result)
    {
        if(!$result->isOK())
            return parent::output($app, $format, $att, $result);
        
        switch($format){
            case "text/xml":
                // 2. Crée le document
                $doc = new XMLDocument("1.0", "utf-8");
                $rootNode = $doc->appendChild($doc->createElement('data'));
                $doc->appendAssocArray($rootNode,$this->att);
                return $doc->SaveXML();
        }
        return parent::output($app, $format, $att, $result);
    }
};

?>