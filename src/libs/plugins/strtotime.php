<?php
use Tplix\Tplix;

class TplixCompileStrtotime
{
    function call(array $params = []): string
    {
        $value = $params[0];
        if($value === null) return "";
        return strtotime($value);
    }
}