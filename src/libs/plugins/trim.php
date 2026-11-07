<?php
/****************
 * {$articleTitle|trim:"'"}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileTrim
{
    public static function call(array $args)
    {
        $text = $args[0] ?? '';
        $trim = $args[1] ?? null;
        if ($trim === null) return trim($text);
        return trim($text, $trim);
    }
}