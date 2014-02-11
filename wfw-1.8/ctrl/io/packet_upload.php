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
 * Envoi les données d'un paquet
 * Rôle : Visiteur
 * UC   : packet_upload
 */

class io_module_packet_upload_ctrl extends cApplicationCtrl{
    public $fields    = array('io_upload_id', 'packet_num', 'packet_size', 'base64_data');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        $mode = $app->getCfgValue("io_module","storage_mode");
        
        if(!IoUploadMgr::getById($upload, $p->io_upload_id))
            return false;
        
        // 1. Vérifie le numéro du paquet
        if($p->packet_num < 0 || $p->packet_num >= $upload->packetCount)
            return RESULT(cResult::Failed,"IO_PACKET_NUM_OVERFLOW");
        
        //offset du packet
        $packet_offset = $upload->packetSize * $p->packet_num;
        
        // 2. Vérifie la taille du paquet reçu 
        if($p->packet_num == $upload->packetCount-1)//dernier paquet ?
            $packet_size   = $upload->fileSize-(($upload->packetCount-1)*$upload->packetSize);
        else
            $packet_size   = $upload->packetSize; //taille du packet
        if($packet_size != $p->packet_size)
            return RESULT(cResult::Failed,"IO_PACKET_SIZE_DIFFER");

        // 3. Vérifie la taille des données décodées
        $data = base64_decode($p->base64_data);
        if(strlen($data) != $packet_size)
            return RESULT(cResult::Failed,"IO_INVALID_DATA_SIZE",array("message"=>(strlen($data)." != $packet_size")));
        
        // 4. Actualise ou insert les données dans la table IO_PACKET
        return $app->callStoredProc('io_set_packet',
            $p->io_upload_id,
            $p->packet_num,
            true,
            $p->base64_data
        );
    }
};


?>