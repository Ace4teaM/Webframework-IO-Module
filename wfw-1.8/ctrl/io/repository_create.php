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
        $remote_ip = (getenv('HTTP_X_FORWARDED_FOR'))? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR'); 
        
        //
        // 1. Crée le dépot
        //
        
        if(!$app->callStoredProc("io_create_repository", $p->io_repository_id, $remote_ip))
            return false;
        $result = cResult::getLast();
        
        $p->io_repository_id = $result->getAtt("io_repository_id");
        
        //
        // 2. Initialise les données du dépot
        //

        //enregistre les arguments
        foreach($this->att as $name=>$value){
            if(!cInputIdentifier::isValid($name))
                continue;
            $app->callStoredProc("io_set_repository_entry", $p->io_repository_id, $name, $value);
        }
        
        // ajoute les parametres d'entree
        foreach($p as $name=>$value){
            if(!cInputIdentifier::isValid($name))
                continue;
            $app->callStoredProc("io_set_repository_entry", $p->io_repository_id, $name, $value);
        }
        
        //
        // 3. Envoie un mail de notification
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
        
        return RESULT(cResult::Ok,cResult::Success,array("repository_id"=>$p->repository_id));
    }

};

?>