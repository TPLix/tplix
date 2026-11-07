<?php
/****************
 * {$chars|str_repeat}
 * {$chars|str_repeat:2}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileStr_repeat
{
    public static function call(array $args)
    {
        $value = $args[0] ?? "";
        $times = $args[1] ?? 2;
        return str_repeat((string)$value,(int)$times);
    }
}
