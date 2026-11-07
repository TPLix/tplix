<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileAbs
{
    public function call(array $args = []): mixed
    {
        $string = isset($args[0]) ? $args[0]:null;
        if($string === null) return "";
        return abs($string);
    }
}