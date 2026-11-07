<?php
/**************
 * {html_image file='templates/1.png'}
 * file	Yes	name/path to image
 * height	No	Height to display image (defaults to actual image height)
 * width	No	Width to display image (defaults to actual image width)
 * basedir	no	Directory to base relative paths from (defaults to web server doc root)
 * alt	no	Alternative description of the image
 * href	no	href value to link the image to
 * path_prefix
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileHtml_image
{
    function call(array $params = []): string
    {
        $file = isset($params["file"]) ? $params["file"] : null;
        $height = isset($params["height"]) ? $params["height"] : null;
        $width = isset($params["width"]) ? $params["width"] : null;
        $basedir = isset($params["basedir"]) ? $params["basedir"] : null;
        $alt = isset($params["alt"]) ? $params["alt"] : null;
        $href = isset($params["href"]) ? $params["href"] : null;
        $href_target = isset($params["href_target"]) ? $params["href_target"] : null;
        $path_prefix = isset($params["path_prefix"]) ? $params["path_prefix"] : null;
        $filePath = "";
        if($basedir !== null) $filePath .=$basedir;
        $filePath .=$file;
        
        if($height === null)
        {
            $imageInfo = getimagesize($filePath);
            $width  = $imageInfo[0];
            $height = $imageInfo[1];
        }

        $output = '';
        if($href !== null)
        {
            $output .= '<a href="'.$href.'"';
            if($href_target !== null) $output .= ' target="'.$href_target.'"';
            $output .= '>';
        }
        $output .= '<img src="'.$filePath.'" width="'.$width.'" height="'.$height.'"';
        if($alt !==null) $output .= ' alt="'.$alt.'"';
        $output .= '>';
        if($href !== null) $output .= '</a>';
        return $output;
    }
}
