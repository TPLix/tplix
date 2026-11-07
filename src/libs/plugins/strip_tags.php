<?php
/****************
 * {$articleTitle|strip_tags}
 * {$articleTitle|strip_tags:false}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileStrip_tags
{
    public static function call(array $args)
    {
        $string = $args[0] ?? "";
        $replace_with_space = $args[1] ?? false;
        if ($replace_with_space) return preg_replace('/<[^>]*>/', ' ', $string);
        return preg_replace('/<[^>]*>/', '', $string);
    }
}
