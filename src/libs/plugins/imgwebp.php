<?php
use Tplix\Tplix;
class TplixCompileImgwebp
{
    function call(array $params = []): string
    {
        $img = isset($params[0]) ? $params[0]: null;
        $assets = isset($params[1]) ? $params[1]: null;
        return $this->ImageToWebp($img,$assets);
    }
    function ImageToWebp($isrc="", $assets="")
    {
        $src="";
        if(!empty($assets)) $src=$assets;
        $src .=$isrc;
        //return $src;
        $quality = 80;
        $destination = "templates_c/ImageToWebp/";
        if (!file_exists($destination)) mkdir($destination, 0755);
        $imageName = basename($src);
        $extension = pathinfo($imageName, PATHINFO_EXTENSION);
        $imageName = str_replace("." . $extension, "", $imageName);
        $NewImage = $destination.$imageName.".webp";
        if (file_exists($NewImage)) return "/".$NewImage;
        // Find the extension of source image.
        $extension = strtolower(strrchr($src, '.' ));
        // Convert image to resource object according to its type.
        switch ($extension)
        {
            case '.jpg':
            case '.jpeg':
            $img = @imagecreatefromjpeg($src);
            break;
            case '.gif':
            $img = @imagecreatefromgif($src);
            break;
            case '.png':
            $img = @imagecreatefrompng($src);
            break;
            default:
            $img = false;
            break;
        }
        //.webp
        if($img === false) return $src;
        if (!imageistruecolor($img)) return $src;

        imagewebp($img, $NewImage, $quality );
        if (file_exists($NewImage)) return "/".$NewImage;
        return $src;
    }    
    
}