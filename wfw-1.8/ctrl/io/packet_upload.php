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
 * Rôle : Administrateur
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
        
        //verifie le numero du paquet
        if($p->packet_num < 0 || $p->packet_num >= $upload->packetCount)
            return RESULT(cResult::Failed,"IO_PACKET_NUM_OVERFLOW");
        
         //offset du packet
        $packet_offset = $upload->packetSize * $p->packet_num;
        
        //taille du packet
        if($p->packet_num == $upload->packetCount-1)//dernier paquet ?
            $packet_size   = $upload->fileSize-(($upload->packetCount-1)*$upload->packetSize);
        else
            $packet_size   = $upload->packetSize; //taille du packet
        if($packet_size != $p->packet_size)
            return RESULT(cResult::Failed,"IO_PACKET_SIZE_DIFFER");

        //décode les données
        $data = base64_decode($p->base64_data);

        //verifie la taille des données
        if(strlen($data) != $packet_size)
            return RESULT(cResult::Failed,"IO_INVALID_DATA_SIZE",array("message"=>(strlen($data)." != $packet_size")));
        
        // Ecrit les données a la volé dans le fichier
        if($mode == "file")
        {
            $upload_file_name  = $upload->uploadPath."/".$p->io_upload_id;
            
            // ouvre le fichier en ecriture
            if(!($fp = fopen($upload_file_name, "c")))
                return RESULT(cResult::Failed, "IO_FILE_OPEN");

            // verifie les limites
            $fstat = fstat($fp);
            $fp_size = intval($fstat['size']);
            //print_r($fstat); exit;
            if($packet_offset+$packet_size > $fp_size)
            {
                fclose($fp);
                return RESULT(cResult::Failed, "IO_FILE_OVERFLOW");
            }
            
            // deplace a l'offset
            if(fseek($fp,$packet_offset,SEEK_SET)!=0)
            {
                fclose($fp);
                return RESULT(cResult::Failed, "IO_FILE_SEEK_ERROR");
            }
            // ecrit les données
            if(!fwrite($fp, $data))
            {
                fclose($fp);
                return RESULT(cResult::Failed, "IO_FILE_WRITE_ERROR");
            }
            fclose($fp);
        }

        //envoi les données en base
        return $app->callStoredProc('io_set_packet',
            $p->io_upload_id,
            $p->packet_num,
            true,
            $p->base64_data
        );
    }
};


?>