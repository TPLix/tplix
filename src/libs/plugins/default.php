<?php
use Tplix\Tplix;

class TplixCompileDefault
{
    public function call(array $args = []): string
    {
        $value = isset($args[0]) ? $args[0]:null;
        $default = isset($args[1]) ? $args[1]:null;
        return ($value===null || $value==='') ? $default:$value;
    }
}