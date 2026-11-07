<?php
use Tplix\Tplix;
use Tplix\Plugins;
 /*****
 * Tplix to_charset modifier plugin
 * Type:     modifier
 * Name:     to_charset
 * Purpose:  convert character encoding from internal encoding to $charset
 *****/

class TplixCompileTo_charset
{
    public function call(array $params = []): string
    {
        $params_0     = $params[0] ?? '';
        $params_1     = $params[1] ?? '';
        $_mbstring  = Tplix::getInstance()->_MBSTRING;
        if (!$_mbstring) return $params_0;
        if (!$params_1) $params_1 = "ISO-8859-1";
        $_charset  = Tplix::getInstance()->charset;
        return mb_convert_encoding($params_0,$params_1,addslashes(Smarty::$_charset));
    }
}