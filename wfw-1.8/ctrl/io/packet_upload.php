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

/*
 * Envoie un message
 * Rôle : Administrateur
 * UC   : mail_send_message
 */

class Ctrl extends cApplicationCtrl{
    public $fields    = array('to', 'subject', 'msg');
    public $op_fields = array( 'from', 'from_name', 'server_adr', 'port_num', 'notify', 'content_type' );

    function main(iApplication $app, $app_path, $p) {

        //champs par défauts
        if(!$p->from)
            $p->from = $app->getCfgValue("mail_module","from");

        if(!$p->from_name)
            $p->from_name = $app->getCfgValue("mail_module","from_name");

        if(!$p->server_adr)
            $p->server_adr = $app->getCfgValue("mail_module","server_adr");

        if(!$p->port_num)
            $p->port_num = $app->getCfgValue("mail_module","port_num");

        if(!$p->content_type)
            $p->content_type = "text/plain";

        if(!$p->notify)
            $p->notify = NULL;

        //initialise l'objet MailMessage
        $msg = new MailMessage();
        $msg->to       = $p->to;
        $msg->subject  = $p->subject;
        $msg->msg      = $p->msg;
        $msg->from     = $p->from;
        $msg->fromName = $p->from_name;
        $msg->notify   = $p->notify;
        $msg->contentType   = $p->content_type;

        //initialise l'objet MailServer
        $server = new MailServer();
        $server->serverAdr = $p->server_adr;
        $server->portNum   = $p->port_num;

        //envoie le message
        if(!MailModule::sendMessage($msg,$server))
            return false;
        
        return RESULT_OK();
    }
};


?>