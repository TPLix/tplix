<?php
/****************
 * {$num|number_format}
 * {$num|number_format:2}
 * {$num|number_format:2:".":","}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileNumber_format
{
    public function call(array $args = []): mixed
    {
        $value = isset($args[0]) ? $args[0]:0;
        $value = rtrim(ltrim($value,"'"),"'");
        $decimals = isset($args[1]) ? $args[1]:0;
        $decimalPoint = isset($args[2]) ? $args[2]:".";
        $decimalSeparator = isset($args[3]) ? $args[3]:",";
        return number_format($value,$decimals,$decimalPoint,$decimalSeparator);
    }
}