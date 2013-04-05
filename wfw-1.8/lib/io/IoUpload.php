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
 *  Webframework Module
 *  PHP Data-Model Implementation
*/


/**
* @author       developpement
*/
class IoUpload
{
    
    /**
    * @var      String
    */
    public $ioUploadId;
    
    /**
    * @var      String
    */
    public $checksum;
    
    /**
    * @var      int
    */
    public $packetSize;
    
    /**
    * @var      String
    */
    public $filename;
    
    /**
    * @var      String
    */
    public $outputPath;
    
    /**
    * @var      String
    */
    public $uploadPath;
    
    /**
    * @var      String
    */
    public $clientIp;
    
    /**
    * @var      Date
    */
    public $beginDate;
    
    /**
    * @var      int
    */
    public $fileSize;
    
    /**
    * @var      boolean
    */
    public $uploadComplete;
    
    /**
    * @var      int
    */
    public $packetCount;    

}

/*
   IO_Upload Class manager
   
   This class is optimized for use with the Webfrmework project (www.webframework.fr)
*/
class IoUploadMgr
{
    /**
     * @brief Convert existing instance to XML element
     * @param $inst Entity instance (IoUpload)
     * @param $doc Parent document
     * @return New element node
     */
    public static function toXML(&$inst,$doc) {
        $node = $doc->createElement(strtolower("IoUpload"));
        
        $node->appendChild($doc->createTextElement("io_upload_id",$inst->ioUploadId));
        $node->appendChild($doc->createTextElement("checksum",$inst->checksum));
        $node->appendChild($doc->createTextElement("packet_size",$inst->packetSize));
        $node->appendChild($doc->createTextElement("filename",$inst->filename));
        $node->appendChild($doc->createTextElement("output_path",$inst->outputPath));
        $node->appendChild($doc->createTextElement("upload_path",$inst->uploadPath));
        $node->appendChild($doc->createTextElement("client_ip",$inst->clientIp));
        $node->appendChild($doc->createTextElement("begin_date",$inst->beginDate));
        $node->appendChild($doc->createTextElement("file_size",$inst->fileSize));
        $node->appendChild($doc->createTextElement("upload_complete",$inst->uploadComplete));
        $node->appendChild($doc->createTextElement("packet_count",$inst->packetCount));       

          
        return $node;
    }
    
    
    /*
      @brief Get entry list
      @param $list Array to receive new instances
      @param $cond SQL Select condition
      @param $db iDataBase derived instance
    */
    public static function getAll(&$list,$cond,$db=null){
       $list = array();
      
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
      //execute la requete
       $query = "SELECT * from IO_Upload where $cond";
       if(!$db->execute($query,$result))
          return false;
       
      //extrait les instances
       $i=0;
       while( $result->seek($i,iDatabaseQuery::Origin) ){
        $inst = new IoUpload();
        IoUploadMgr::bindResult($inst,$result);
        array_push($list,$inst);
        $i++;
       }
       
       return RESULT_OK();
    }
    
    /*
      @brief Get single entry
      @param $inst IoUpload instance pointer to initialize
      @param $cond SQL Select condition
      @param $db iDataBase derived instance
    */
    public static function bindResult(&$inst,$result){
          $inst->ioUploadId = $result->fetchValue("io_upload_id");
          $inst->checksum = $result->fetchValue("checksum");
          $inst->packetSize = $result->fetchValue("packet_size");
          $inst->filename = $result->fetchValue("filename");
          $inst->outputPath = $result->fetchValue("output_path");
          $inst->uploadPath = $result->fetchValue("upload_path");
          $inst->clientIp = $result->fetchValue("client_ip");
          $inst->beginDate = $result->fetchValue("begin_date");
          $inst->fileSize = $result->fetchValue("file_size");
          $inst->uploadComplete = $result->fetchValue("upload_complete");
          $inst->packetCount = $result->fetchValue("packet_count");          

       return true;
    }
    
    /*
      @brief Get single entry
      @param $inst IoUpload instance pointer to initialize
      @param $cond SQL Select condition
      @param $db iDataBase derived instance
    */
    public static function get(&$inst,$cond,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
      //execute la requete
       $query = "SELECT * from IO_Upload where $cond";
       if($db->execute($query,$result)){
            $inst = new IoUpload();
             if(!$result->rowCount())
                 return RESULT(cResult::Failed,iDatabaseQuery::EmptyResult);
          return IoUploadMgr::bindResult($inst,$result);
       }
       return false;
    }
    
    /*
      @brief Get single entry by id
      @param $inst IoUpload instance pointer to initialize
      @param $id Primary unique identifier of entry to retreive
      @param $db iDataBase derived instance
    */
    public static function getById(&$inst,$id,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
      //execute la requete
       $query = "SELECT * from IO_Upload where IO_Upload_id=".$db->parseValue($id);
       if($db->execute($query,$result)){
            $inst = new IoUpload();
             if(!$result->rowCount())
                 return RESULT(cResult::Failed,iDatabaseQuery::EmptyResult);
             self::bindResult($inst,$result);
          return true;
       }
       return false;
    }
    
   /*
      @brief Update single entry by id
      @param $inst WriterDocument instance pointer to initialize
      @param $id Primary unique identifier of entry to retreive
      @param $db iDataBase derived instance
    */
    public static function update(&$inst,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
       //id initialise ?
       if(!isset($inst->ioUploadId))
           return RESULT(cResult::Failed, cApplication::entityMissingId);
      
      //execute la requete
       $query = "UPDATE IO_Upload SET";
       $query .= " io_upload_id =".$db->parseValue($inst->ioUploadId).",";
       $query .= " checksum =".$db->parseValue($inst->checksum).",";
       $query .= " packet_size =".$db->parseValue($inst->packetSize).",";
       $query .= " filename =".$db->parseValue($inst->filename).",";
       $query .= " output_path =".$db->parseValue($inst->outputPath).",";
       $query .= " upload_path =".$db->parseValue($inst->uploadPath).",";
       $query .= " client_ip =".$db->parseValue($inst->clientIp).",";
       $query .= " begin_date =".$db->parseValue($inst->beginDate).",";
       $query .= " file_size =".$db->parseValue($inst->fileSize).",";
       $query .= " upload_complete =".$db->parseValue($inst->uploadComplete).",";
       $query .= " packet_count =".$db->parseValue($inst->packetCount).",";
       $query = substr($query,0,-1);//remove last ','
       $query .= " where IO_Upload_id=".$db->parseValue($inst->ioUploadId);
       if($db->execute($query,$result))
          return true;

       return false;
    }
    
   /** @brief Convert name to code */
    public static function nameToCode($name){
        for($i=strlen($name)-1;$i>=0;$i--){
            $c = substr($name, $i, 1);
            if(strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ",$c) !== FALSE){
                $name = substr_replace($name,($i?"_":"").strtolower($c), $i, 1);
            }
        }
        return $name;
    }
    
    /**
      @brief Get entry by id's relation table
      @param $inst IoUpload instance pointer to initialize
      @param $obj An another entry class object instance
      @param $db iDataBase derived instance
    */
    public static function getByRelation(&$inst,$obj,$db=null){
        $objectName = get_class($obj);
        $objectTableName  = IoUploadMgr::nameToCode($objectName);
        $objectIdName = lcfirst($objectName)."Id";
        
        /*print_r($objectName.", ");
        print_r($objectTableName.", ");
        print_r($objectIdName.", ");
        print_r($obj->$objectIdName);*/
        
        $select;
        if(is_string($obj->$objectIdName))
            $select = ("IO_Upload_id = (select IO_Upload_id from $objectTableName where ".$objectTableName."_id='".$obj->$objectIdName."')");
        else
            $select = ("IO_Upload_id = (select IO_Upload_id  from $objectTableName where ".$objectTableName."_id=".$obj->$objectIdName.")");

        return IoUploadMgr::get($inst,$select,$db);
    }

}

?>