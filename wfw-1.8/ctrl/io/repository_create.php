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
 * Crée un nouveau dossier de données
 * Rôle : Visiteur
 * UC   : repository_create
 */

class io_module_repository_create_ctrl extends cApplicationCtrl{
    public $fields    = array();
    public $op_fields = array('repository_id', 'repository_pwd', 'repository_type', 'is_readonly', 'is_event', 'use_data', 'note');
    
    function main(iApplication $app, $app_path, $p)
    {
        $timestamp   = time();
        
        //
        // 1. Génère le nom de dépôt
        //
        if($p->repository_id === null)
            $p->repository_id = (rand(100,900).'-'.$timestamp);
        
        //
        $file_path = $app->getCfgValue("io_module","repository_data_path")."/".$p->repository_id.".xml";
        $data_path = $app->getCfgValue("io_module","repository_data_path")."/".$p->repository_id;
        
        //
        // 2. érifie si le dossier existe
        //
        if(file_exists($file_path))
            return RESULT(cResult::Failed, IOModule::RepositoryAlreadyExists);


        //
        // 3. Crée le document XML avec l'ensemble des données reçues en paramètres
        //
        $fields_doc = new XMLDocument("1.0", "utf-8");
        $fields_doc->appendChild($fields_doc->createElement('data'));
        
        //enregistre les arguments
        foreach($this->att as $name=>$value){
            if(!cInputIdentifier::isValid($name))
                continue;
            $fields_doc->documentElement->appendChild($fields_doc->createTextElement($name,$value));
        }
        
        // ajoute les parametres d'entree
        $fields_doc->appendAssocArray($fields_doc->documentElement,$p);
        
        // ajoute l'IP du client
        $remote_ip = (getenv('HTTP_X_FORWARDED_FOR'))? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR'); 
        $fields_doc->documentElement->appendChild($fields_doc->createTextElement('remote_ip',$remote_ip));
        
        // ajoute la date actuelle
        $fields_doc->documentElement->appendChild($fields_doc->createTextElement('timestamp',$timestamp));
        
        // ajoute la nom du fichier
        $fields_doc->documentElement->appendChild($fields_doc->createTextElement('filename',$p->repository_id.".xml"));
        
        //
        // Sauvegarde le fichier XML
        //
        if(!$fields_doc->save($file_path))
            return RESULT(cResult::Failed, cApplication::CantCreateResource, array("file"=>$file_path));
        chmod($file_path,0644);

        //
        // 4. Crée le dossier de données (si besoin)
        //      
        if(($p->use_data) && !file_exists($data_path) && (cmd("mkdir ".$data_path,$cmd_out)!=0))
            return RESULT(cResult::Failed, cApplication::CantCreateResource, array("file"=>$data_path));

        //
        // 5. Envoie un mail de notification
        //
        $template = $app->getCfgValue("io_module","repository_notify_template");
        $mail     = $app->getCfgValue("io_module","repository_notify_mail");
        $bNotify  = $app->getCfgValue("io_module","use_repository_notify");
        if($bNotify && $mail && !class_exists("MailModule")){
            $msg = new MailMessage();
            $msg->to       = $p->user_mail;
            $msg->subject  = "Nouveau Dépot";

            //attributs du template
            $template_att = objectToArray($p);

            //depuis un template ?
            if($template && file_exists($template)){
                $msg->msg         = cHTMLTemplate::transformFile($template,$template_att);
                $msg->contentType = mime_content_type($template);
            }
            //depuis le message standard ?
            else{
                $msg->msg         = cHTMLTemplate::transform($default->getResultText("messages","IO_REPOSITORY_NOTIFY_MAIL"),$template_att);
                $msg->contentType = "text/plain";
            }

            //envoie le message
            if(!MailModule::sendMessage($msg))
                return false;
        }
        
        //
        // 6. Attache un événement
        //
        if($p->is_event){
            $link_filename = $app->getCfgValue("io_module","repository_event_path")."/".$p->repository_id;
            
            if(file_exists($link_filename))
                unlink($link_filename);

            if(!symlink($file_path, $link_filename)) 
                return RESULT(cResult::System, IOModule::CantLinkEvent);
        }
        
        return RESULT(cResult::Ok,cResult::Success,array("repository_id"=>$p->repository_id));
    }

};

?>