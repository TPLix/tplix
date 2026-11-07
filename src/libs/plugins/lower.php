<?php
use Tplix\Tplix;

class TplixCompileLower
{
    function call(array $params = []): string
    {
        $value = $params[0];
        if($value === null) return "";
        $charset = Tplix::getInstance()->charset;
        return mb_strtolower($value,$charset);
    }
}