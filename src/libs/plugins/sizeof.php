<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileSizeof
{
    public function call(array $args = []): mixed
    {
        $string = isset($args[0]) ? $args[0]:[];
        return is_array($string) ? sizeof($var) : 0;
    }
}