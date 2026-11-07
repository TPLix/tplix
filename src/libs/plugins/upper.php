<?php
use Tplix\Tplix;

class TplixCompileUpper
{
    function call(array $params = []): string
    {
        $value = $params[0];
        if($value === null) return "";
        $charset = Tplix::getInstance()->charset;
        return mb_strtoupper($value,$charset);
    }
}