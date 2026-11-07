<?php
/****************
 * {$articleTitle|rtrim:"'"}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileRtrim
{
    public static function call(array $args)
    {
        $text = $args[0] ?? '';
        $trim = $args[1] ?? null;
        if ($trim === null) return rtrim($text);
        return rtrim($text, $trim);
    }
}