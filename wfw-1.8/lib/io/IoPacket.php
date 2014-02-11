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
class IoPacket
{
   public function getId(){
      return $this->ioPacketId;
  }
   public function setId($id){
      return $this->ioPacketId = $id;
  }

    
    /**
    * @var      int
    */
    public $ioPacketId;
    
    /**
    * @var      String
    */
    public $base64Data;
    
    /**
    * @var      boolean
    */
    public $packetStatus;
    
    /**
    * @var      int
    */
    public $packetNum;    

}

/*
   IO_packet Class manager
   
   This class is optimized for use with the Webfrmework project (www.webframework.fr)
*/
class IoPacketMgr
{
    /**
     * @brief Convert existing instance to XML element
     * @param $inst Entity instance (IoPacket)
     * @param $doc Parent document
     * @return New element node
     */
    public static function toXML(&$inst,$doc) {
        $node = $doc->createElement(strtolower("IoPacket"));
        
        $node->appendChild($doc->createTextElement("io_packet_id",$inst->ioPacketId));
        $node->appendChild($doc->createTextElement("base64_data",$inst->base64Data));
        $node->appendChild($doc->createTextElement("packet_status",$inst->packetStatus));
        $node->appendChild($doc->createTextElement("packet_num",$inst->packetNum));       

          
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
       $query = "SELECT * from IO_packet where $cond";
       if(!$db->execute($query,$result))
          return false;
       
      //extrait les instances
       $i=0;
       while( $result->seek($i,iDatabaseQuery::Origin) ){
        $inst = new IoPacket();
        IoPacketMgr::bindResult($inst,$result);
        array_push($list,$inst);
        $i++;
       }
       
       return RESULT_OK();
    }
    
    /*
      @brief Get single entry
      @param $inst IoPacket instance pointer to initialize
      @param $cond SQL Select condition
      @param $db iDataBase derived instance
    */
    public static function bindResult(&$inst,$result){
          $inst->ioPacketId = $result->fetchValue("io_packet_id");
          $inst->base64Data = $result->fetchValue("base64_data");
          $inst->packetStatus = $result->fetchValue("packet_status");
          $inst->packetNum = $result->fetchValue("packet_num");          

       return true;
    }
    
    /*
      @brief Get single entry
      @param $inst IoPacket instance pointer to initialize
      @param $cond SQL Select condition
      @param $db iDataBase derived instance
    */
    public static function get(&$inst,$cond,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
      //execute la requete
       $query = "SELECT * from IO_packet where $cond";
       if($db->execute($query,$result)){
            $inst = new IoPacket();
             if(!$result->rowCount())
                 return RESULT(cResult::Failed,iDatabaseQuery::EmptyResult);
          return IoPacketMgr::bindResult($inst,$result);
       }
       return false;
    }
    
    /*
      @brief Get single entry by id
      @param $inst IoPacket instance pointer to initialize
      @param $id Primary unique identifier of entry to retreive
      @param $db iDataBase derived instance
    */
    public static function getById(&$inst,$id,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
      //execute la requete
       $query = "SELECT * from IO_packet where IO_packet_id=".$db->parseValue($id);
       if($db->execute($query,$result)){
            $inst = new IoPacket();
             if(!$result->rowCount())
                 return RESULT(cResult::Failed,iDatabaseQuery::EmptyResult);
             self::bindResult($inst,$result);
          return true;
       }
       return false;
    }
    
   /*
      @brief Insert single entry with generated id
      @param $inst WriterDocument instance pointer to initialize
      @param $add_fields Array of columns names/columns values of additional fields
      @param $db iDataBase derived instance
    */
    public static function insert(&$inst,$add_fields=null,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
       //id initialise ?
       if(!isset($inst->ioPacketId)){
            $table_name = 'IO_packet';
            $table_id_name = $table_name.'_id';
           if(!$db->execute("select * from new_id('$table_name','$table_id_name');",$result))
              return RESULT(cResult::Failed, cApplication::EntityMissingId);
           $inst->ioPacketId = intval($result->fetchValue("new_id"));
       }
       
      //execute la requete
       $query = "INSERT INTO IO_packet (";
       $query .= " io_packet_id,";
       $query .= " base64_data,";
       $query .= " packet_status,";
       $query .= " packet_num,";
       if(is_array($add_fields))
           $query .= implode(',',array_keys($add_fields)).',';
       $query = substr($query,0,-1);//remove last ','
       $query .= ")";
       
       $query .= " VALUES(";
       $query .= $db->parseValue($inst->ioPacketId).",";
       $query .= $db->parseValue($inst->base64Data).",";
       $query .= $db->parseValue($inst->packetStatus).",";
       $query .= $db->parseValue($inst->packetNum).",";
       if(is_array($add_fields))
           $query .= implode(',',$add_fields).',';
       $query = substr($query,0,-1);//remove last ','
       $query .= ")";
       
       if($db->execute($query,$result))
          return true;

       return false;
    }
    
   /*
      @brief Update single entry by id
      @param $inst WriterDocument instance pointer to initialize
      @param $db iDataBase derived instance
    */
    public static function update(&$inst,$db=null){
       //obtient la base de donnees courrante
       global $app;
       if(!$db && !$app->getDB($db))
         return false;
      
       //id initialise ?
       if(!isset($inst->ioPacketId))
           return RESULT(cResult::Failed, cApplication::EntityMissingId);
      
      //execute la requete
       $query = "UPDATE IO_packet SET";
       $query .= " io_packet_id =".$db->parseValue($inst->ioPacketId).",";
       $query .= " base64_data =".$db->parseValue($inst->base64Data).",";
       $query .= " packet_status =".$db->parseValue($inst->packetStatus).",";
       $query .= " packet_num =".$db->parseValue($inst->packetNum).",";
       $query = substr($query,0,-1);//remove last ','
       $query .= " where IO_packet_id=".$db->parseValue($inst->ioPacketId);
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
      @param $inst IoPacket instance pointer to initialize
      @param $obj An another entry class object instance
      @param $db iDataBase derived instance
    */
    public static function getByRelation(&$inst,$obj,$db=null){
        $objectName = get_class($obj);
        $objectTableName  = IoPacketMgr::nameToCode($objectName);
        $objectIdName = lcfirst($objectName)."Id";
        
        /*print_r($objectName.", ");
        print_r($objectTableName.", ");
        print_r($objectIdName.", ");
        print_r($obj->$objectIdName);*/
        
        $select;
        if(is_string($obj->$objectIdName))
            $select = ("IO_packet_id = (select IO_packet_id from $objectTableName where ".$objectTableName."_id='".$obj->$objectIdName."')");
        else
            $select = ("IO_packet_id = (select IO_packet_id  from $objectTableName where ".$objectTableName."_id=".$obj->$objectIdName.")");

        return IoPacketMgr::get($inst,$select,$db);
    }

}

?>