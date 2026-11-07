<?php
/****************
 * {$articleTitle|count_words}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCeil
{
    public function call(array $args = []): mixed
    {
        $string = isset($args[0]) ? $args[0]:0;
        return ceil($string);
    }
}