<?php
/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2012-2014 Thomas AUGUEY <contact@aceteam.org>
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
 * Détermine le statut d'un envoi
 * Rôle : Visiteur
 * UC   : check_upload
 */

class io_module_check_upload_ctrl extends cApplicationCtrl{
    public $fields    = array('io_upload_id');
    public $op_fields = null;

    function main(iApplication $app, $app_path, $p)
    {
        if(!IoUploadMgr::getById($upload, $p->io_upload_id))
            return false;
        
        // prepare le résultat
        $result = new cResult(cResult::Failed,"IO_FILE_UNCOMPLETED");

        //1. Test l’existence des paquets en base (IO_PACKET). Si un paquet est manquant, les paramètres de retour sont complétés et la fonction retourne IO_FILE_UNCOMPLETED
        for($i=0; $i<$upload->packetCount; $i++)
        {
            // le paquet existe en base ?
            $query = "select count(*) as cnt from io_packet where io_upload_id='$p->io_upload_id' and packet_num=$i and packet_status=TRUE;";
            if(!$app->queryToObject($query,$resp))
                return false;
            // retourne les infos sur le paquet manquant
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
        
        //Ok, tous les paquets sont chargés
        return RESULT(cResult::Ok,"IO_FILE_COMPLETED");
    }
};


?>