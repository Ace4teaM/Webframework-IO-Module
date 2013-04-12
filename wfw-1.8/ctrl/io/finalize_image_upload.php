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
 * Envoie un message
 * Rôle : Administrateur
 * UC   : mail_send_message
 */

class Ctrl extends cApplicationCtrl {

    public $fields = array('io_upload_id', 'rx', 'lx', 'ty', 'by');
    public $op_fields = null;

    private $image = null;
    private $new_image = null;
    
    //libere la memoire
    function free_allocated() {
        if ($this->image !== FALSE)
            imagedestroy($this->image);
        if ($this->new_image !== FALSE)
            imagedestroy($this->new_image);
    }

    function main(iApplication $app, $app_path, $p) {
        //obtient l'upload
        if (!IoUploadMgr::getById($upload, $p->io_upload_id))
            return false;

        //if(!$upload->uploadComplete)
        //    return RESULT();

        // Vérifie si le fichier d'upload existe
        //
        $output_file = $upload->outputPath . "/" . $upload->filename;
        $upload_file_name = $upload->uploadPath . "/" . $upload->ioUploadId;
        if (!file_exists($upload_file_name))
            return RESULT(cResult::Failed, "IO_FILE_NOT_FOUND");

        //
        // cree l'image
        //
        $image = null;
        switch ($upload->contentType) {
            case "image/gif":
                $image = imagecreatefromgif($upload_file_name);
                break;
            case "image/jpg":
            case "image/jpeg":
                $image = imagecreatefromjpeg($upload_file_name);
                break;
            case "image/png":
                $image = imagecreatefrompng($upload_file_name);
                break;
            case "image/wbmp":
                $image = imagecreatefromwbmp($upload_file_name);
                break;
            case "image/xbmp":
                $image = imagecreatefromxbmp($upload_file_name);
                break;
            case "image/xpm":
                $image = imagecreatefromxpm($upload_file_name);
                break;
            default:
                return RESULT(cResult::Failed, "IO_NOT_IMAGE_CONTENT_TYPE");
        }
        $this->image = $image;

        //active la transparence
        imagealphablending($image, true);

        //obtient les dimentions de l'image
        list($org_w, $org_h) = getimagesize($upload_file_name);

        // verifie les dimention de la selection
        if($p->lx<0 || $p->ty<0 || $p->rx>=$org_w || $p->by>=$org_h)
            return RESULT(cResult::Failed, "IO_INVALID_SRC_SIZE");
    
        //
        // Calcule le rectangle source (en pixels)
        //
        /*$src_rect = (object)array(
            "x1" => intval($p->x1f * $org_w),
            "y1" => intval($p->y1f * $org_h),
            "x2" => intval($p->x2f * $org_w),
            "y2" => intval($p->y2f * $org_h) 
        );*/
        $src_w = $p->rx - $p->lx;
        $src_h = $p->by - $p->ty;

        //if (!$src_w || !$src_h)
        //    return RESULT(cResult::Failed, "IO_INVALID_SRC_SIZE");

        //
        // Calcule la taille de destination (en pixels)
        //
        $size  = 256;
        if ($src_h > $src_w) {
            $dst_w = intval(($size / $src_h) * $src_w);
            $dst_h = $size;
        } else {
            $dst_w = $size;
            $dst_h = intval(($size / $src_w) * $src_h);
        }

        if (!$dst_w || !$dst_h)
            return RESULT(cResult::Failed, "IO_INVALID_DST_SIZE");

        //
        // Crée la nouvelle image
        //
        $this->new_image = $new_image = imagecreatetruecolor($dst_w, $dst_h);
        if (!$new_image) {
            $this->free_allocated();
            return RESULT(cResult::Failed, "IO_CREATE_IMAGE");
        }
        imagealphablending($new_image, true);
        imagesavealpha($new_image, true);

        //copie dans la nouvelle image
        imagecopyresampled($new_image, $image, 0, 0, $p->lx, $p->ty, $dst_w, $dst_h, $src_w, $src_h);

        //
        // Sauvegarde l'image
        //
        switch ($upload->contentType) {
            case "image/gif":
                $save_result = imagegif($new_image, $output_file);
                break;
            case "image/jpg":
            case "image/jpeg":
                $save_result = imagejpeg($new_image, $output_file, 100);
                break;
            case "image/png":
                $save_result = imagepng($new_image, $output_file);
                break;
            case "image/wbmp":
                $save_result = imagewbmp($new_image, $output_file);
                break;
            case "image/xbm":
                $save_result = imagexbm($new_image, $output_file);
                break;
            default://xpm, xbmp
                $this->free_allocated();
                return RESULT(cResult::Failed, "IO_UNSUPORTED_OUTPUT_FORMAT");
        }

        //
        // Renomme le fichier
        //

        // sauvegarde ok ?
        if (!$save_result) {
            $this->free_allocated();
            return RESULT(cResult::Failed, "IO_CANT_SAVE_FILE");
        }

        // libere la memoire
        $this->free_allocated();

        //termine
        return RESULT(cResult::Ok, "SUCCESS", array("filename", $output_file));
    }

}

;
?>