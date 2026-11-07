<?php
/****************
 * {$articleTitle|strip}
 * {$articleTitle|strip:'&nbsp;'}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileStrip
{
    public static function call(array $args)
    {
        $value = $args[0] ?? "";
        return preg_replace('![\t\r\n ]+!', ' ', $value);
    }
}
