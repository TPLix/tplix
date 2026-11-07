<?php
/****************
 * {$number|string_format:"%.2f"}
 * {$number|string_format:"%d"}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileString_format
{
    public static function call(array $args)
    {
        $value = $args[0] ?? "";
        $format = $args[1] ?? null;
        if($format === null) return $value;
        return sprintf($format,$value);
    }
}
