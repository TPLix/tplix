<?php
/****************
 * {$articleTitle|strlen}
 * {156|strlen}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileStrlen
{
    public static function call(array $args)
    {
        return strlen($args[0]);
    }
}
