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
 * Détermine le statut d'un envoie
 * Rôle : Administrateur
 * UC   : check_upload
 */

class io_module_check_upload_ctrl extends cApplicationCtrl{
    public $fields    = array('io_upload_id');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        if(!IoUploadMgr::getById($upload, $p->io_upload_id))
            return false;
        
        //termine
        //if($upload->uploadComplete == "TRUE")
        //    return RESULT(cResult::Ok,"IO_FILE_COMPLETED");
        
        $result = new cResult(cResult::Failed,"IO_FILE_UNCOMPLETED");
        /*$result->addAtt("status","UNCOMPLETED");
        $result->addAtt("packet_count",$upload->packetCount);
        $result->addAtt("packet_size",$upload->packetSize);
        $result->addAtt("file_size",$upload->fileSize);
        $result->addAtt("last_packet_size",$upload->fileSize-(($upload->packetCount-1)*$upload->packetSize));
        $result->addAtt("last_packet_num",$upload->packetCount-1);*/
        for($i=0; $i<$upload->packetCount; $i++)
        {
            $query = "select count(*) as cnt from io_packet where io_upload_id='$p->io_upload_id' and packet_num=$i and packet_status=TRUE;";
            if(!$app->queryToObject($query,$resp))
                return false;
            if($resp->cnt == "0"){
                $result->addAtt("packet_num",$i);
                $result->addAtt("packet_offset",$upload->packetSize*$i);
                if($i == $upload->packetCount-1)//dernier paquet ? alors la taille n'est pas fixe
                    $result->addAtt("packet_size",$upload->fileSize-(($upload->packetCount-1)*$upload->packetSize));
                else
                    $result->addAtt("packet_size",$upload->packetSize);
                return RESULT_INST($result);
            }
        }
        return RESULT(cResult::Ok,"IO_FILE_COMPLETED");
    }
};


?>