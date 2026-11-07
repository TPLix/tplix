<?php
/****************
 * {$articleTitle|strlen}
 * {156|strlen}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileMax
{
    public static function call(array $args)
    {
        return max($args);
    }
}
