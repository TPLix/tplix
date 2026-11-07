<?php
/****************
 * {$articleTitle|print}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompilePrint
{
    public function call(array $args = []): mixed
    {
        $value = isset($args[0]) ? $args[0]:null;
        return print_r($value);
    }
}