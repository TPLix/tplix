<?php
/****************
 * {$articleTitle|strlen}
 * {156|strlen}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileMin
{
    public static function call(array $args)
    {

        return min($args);
    }
}
