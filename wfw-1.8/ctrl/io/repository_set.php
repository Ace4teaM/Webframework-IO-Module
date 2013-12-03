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
 * Actualise un dossier de données
 * Rôle : Visiteur, Administrateur
 * UC   : repository_set
 */

class io_module_repository_set_ctrl extends cApplicationCtrl{
    public $fields    = array('repository_id');
    public $op_fields = array('repository_pwd');
    
    function acceptedRole(){
        return cApplication::UserRole | cApplication::AdminRole;
    }

    function main(iApplication $app, $app_path, $p)
    {
        $timestamp   = time();
        $file_path = $app->getCfgValue("io_module","repository_data_path")."/".$p->repository_id.".xml";
        $data_path = $app->getCfgValue("io_module","repository_data_path")."/".$p->repository_id;
        
        //
        // 1 Vérifie si le dossier existe
        //
        if(!file_exists($file_path))
            return RESULT(cResult::Failed, IOModule::RepositoryNotExists);

        //
        // 2. Charge le document XML
        //
        $fields_doc = new XMLDocument("1.0", "utf-8");
        $fields_doc->load($file_path);
        
        //
        // 3. Vérifie le mot-de-passe (mode utilisateur)
        //
        if($this->hasRole() & Application::UserRole){
            $node = $fields_doc->one("repository_pwd",$fields_doc->documentElement);
            if($node && !empty($node->nodeValue) && $node->nodeValue != $p->repository_pwd)
                return RESULT(cResult::Failed, IOModule::InvalidPassword);
        }
        
        //
        // 4. Enregistre les arguments
        //
        foreach($this->att as $name=>$value){
            if(!cInputIdentifier::isValid($name))
                continue;
            $node = $fields_doc->one($name,$fields_doc->documentElement);
            if($node)
                $node->nodeValue = $value;
            else
                $fields_doc->documentElement->appendChild($fields_doc->createTextElement($name,$value));
        }

        //
        // Sauvegarde le fichier XML
        //
        if(!$fields_doc->save($file_path))
            return RESULT(cResult::Failed, cApplication::CantCreateResource, array("file"=>$file_path));

        return RESULT(cResult::Ok,cResult::Success,array("repository_id"=>$p->repository_id));
    }

};

?>