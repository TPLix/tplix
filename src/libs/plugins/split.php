<?php
/****************
 * {$chars|split}
 * {$chars|split:","}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileSplit
{
    public static function call(array $args)
    {
        [$value,$delimiter] = $args;
        $delimiter = $delimiter ?? "";
        $value = str_replace([', ', " ,"," , "],'', $value);
        // Handle empty string delimiter → split into UTF-8 chars
        if ($delimiter === ''){
            // UTF-8 aware character split
            return preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        // Normal split (explode)
        $delimiter = str_replace(['"', "'"," "],'', $delimiter);
        return explode($delimiter, $value);
    }
}
