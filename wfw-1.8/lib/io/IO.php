<?php
/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2012-2013 Thomas AUGUEY <contact@aceteam.org>
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

/**
 * Gestionnaire de courriers électroniques
 * Librairie PHP5
 */


require_once("php/class/bases/iModule.php");
require_once("php/class/bases/socket.php");
require_once("php/xml_default.php");

    
class MailModule implements iModule
{
    const MailSended = "MAIL_SENDED";
    const MailSending = "MAIL_SENDING";
    const LoadTemplate = "CANT_LOAD_TEMPLATE";
    
    /**
     * @brief Initialise le module
     * @param $local_path Chemin d'accès local vers ce dossier
     */
    public static function load($local_path){
        global $app;
        
        //chemins d'acces 
        //$this_path = dirname(__FILE__);
        //$this_relative_path = relativePath($this_path,$local_path);
        
        //print_r($this_path);
        
        //initialise la configuration
        $modParam = parse_ini_file("$local_path/config.ini", true);
        $app->config = array_merge_recursive($modParam,$app->config);

        //inclue le model de données
        require_path($local_path."/".$app->getCfgValue("mail_module","lib_path"));
    }
    
    public static function libPath(){
        global $app;
        return $app->getLibPath("mail_mod").$app->getCfgValue("mail_module","lib_path");
    }
    
    public static function makeView($name,$attributes,$template_file){ 
    }
    
    /** 
     * Envoi un message
     * 
     * @param MailMessage $msg Instance du message
     * @param MailServer $server Optionnel, instance du serveur
     * @return bool Résultat de la procédure
     */
    public static function sendMessage(MailMessage $msg,MailServer $server = NULL){ 
        global $app;
        $db=null;
        
        //server par defaut ?
        if($server === NULL){
            $server = new MailServer();
            $server->serverAdr = $app->getCfgValue("mail_module","server_adr");
            $server->portNum   = $app->getCfgValue("mail_module","port_num");
        }

        // sujet
        $subject = '=?UTF-8?B?'.base64_encode($msg->subject).'?=';
        
        // nom de l'expediteur
        $from_name = "";
        if(!empty($msg->from_name))
                $from_name = '=?UTF-8?B?'.base64_encode($msg->from_name).'?=';

        // initialise la connexion
        global $sock;
        $sock = new cSocket();
        $rsp  = "";
        
        //--------------------------------------------------------------
        // fabrique le corps du message

        /*$content = "";
        $content_type = "text/plain";
        if($msg->template){
            $template_path = $app->getCfgValue("mail_module","template_path")."/".$msg->template;
            $template_type = mime_content_type( $template_path );
        }
        else {
            $template_type="text/plain";
        }
        switch($template_type){
            //HTML
            case "text/html":
                //transforme le document 
                $template = new cXMLTemplate();
                if(!$template->Initialise($template_path,NULL,NULL,NULL,$_REQUEST))
                        RESULT(cResult::Failed,MailModule::LoadTemplate);
                $content = $template->Make();
                $content_type = "text/html";
                break;
            //TEXT
            default:
                $content_type = "text/plain";
                $content = $msg->msg;
                break;
        }*/

        //--------------------------------------------------------------
        // puts
        // envoie une commande et verifie si c'est un succees, dans le cas echeant prepare la fin de la communication avec le socket
        //
        function puts($cmd){
            global $sock;
            global $rsp;

            $rsp = $sock->Puts($cmd);
 //           echo($cmd);
 //           echo($rsp);
            //verifie le code de retourne, accept les messages 2xx et 3xx. retourne le reste comme erreur
            $code_char = substr($rsp,0,1);
            if($code_char != "2" && $code_char != "3"){
                return RESULT(cResult::Failed, MailModule::MailSending, array("message"=>"MAIL_CMD_FAILED","cmd"=>$cmd,"response"=>$rsp));
            }

            return $rsp;
        }

        //--------------------------------------------------------------
        // envoie le mail
        $to = $msg->to;

        //ouvre la connection
        if(!$sock->Open($server->serverAdr,$server->portNum))
            return false; // conserve le resultat

        //authentification
        if(!puts("HELO ".(isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:"noname")."\n")) goto onerror;

        //expediteur
        if(!puts('MAIL FROM: <'.$msg->from.">\n")) goto onerror;

        //destinataire
        if(!puts("RCPT TO: <$to>\n")) goto onerror;

        //passe en mode data pour ecrire le contenu du mail
        if(!puts("DATA\n")) goto onerror;

        //construit et envoie le corps du message     
        $data = "";
        $data .= 'Content-Type: '.$msg->contentType.'; charset=UTF-8'."\n";
        $data .= 'Content-Transfer-Encoding: 8bit'."\n";
        $data .= "To: $to\n";
        if(!empty($from_name))
          $data .= 'From: "'.$from_name.'" <'.$from.">\n"; 
        else
          $data .= 'From: '.$msg->from."\n";
        $data .= 'Subject: '.$subject."\n";
        $data .= "\n";
        $data .= $msg->msg."\n";
        $data .= "\r\n.\r\n"; // fin de corps du message
        if(!puts($data)) goto onerror;

        // quit 
        $sock->Puts("QUIT\n");
        $sock->Close();

        goto ok;

        // ERREUR 
        onerror:
        // quit 
        $sock->Puts("QUIT\n");
        $sock->Close();
        return false; // erreur gérée par la fonction Puts()

        // OK
        ok:
        return RESULT(cResult::Ok, MailModule::MailSended);
    }
    
}

?>
