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

    
class IOModule implements iModule
{
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
        require_path($local_path."/".$app->getCfgValue("io_module","lib_path"));
    }
    
    public static function libPath(){
        global $app;
        return $app->getLibPath("io_mod").$app->getCfgValue("io_module","lib_path");
    }
    
    public static function makeView($name,$attributes,$template_file){ 
    }
    
}

?>
