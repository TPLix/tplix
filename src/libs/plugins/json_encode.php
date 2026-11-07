<?php
/****************
 * {$articleTitle|json_encode}
 * {$articleTitle|json_encode:16}
 * 2nd parameter as per https://www.php.net/json_encode
 * 3rd parameter as depth
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileJson_encode
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        $bitmask = isset($args[1]) ? $args[1]:0;
        $depth = isset($args[2]) ? $args[2]:0;
        $bitmask = (int)$bitmask>0 ? $bitmask:null;
        $depth = (int)$depth>0 ? $depth:null;
        if($bitmask === null) return json_encode($value);
        if($depth === null) return json_encode($value,$bitmask);
        return json_encode($value,$bitmask,$depth);
    }
}