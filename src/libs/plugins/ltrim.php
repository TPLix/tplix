<?php
/****************
 * {$articleTitle|ltrim:"'"}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileLtrim
{
    public static function call(array $args)
    {
        $text = $args[0] ?? '';
        $trim = $args[1] ?? null;
        if ($trim === null) return ltrim($text);
        return ltrim($text, $trim);
    }
}