<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileEmpty
{
    public function call(array $args = []): mixed
    {
        $string = isset($args[0]) ? $args[0]:"";
        $not = isset($args[1]) ? $args[1]:null;
        if($not == "!") return !empty($string);
        return empty($string);
    }
}