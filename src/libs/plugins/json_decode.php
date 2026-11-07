<?php
/****************
 * {$articleTitle|json_decode}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileJson_decode
{
    public function call(array $args = []): mixed
    {
        $value = isset($args[0]) ? $args[0]:null;
        $flag = isset($args[1]) ? $args[1]:null;
        if($flag === true) return json_decode($value,true);
        return json_decode($value);
    }
}