<?php
use Tplix\Tplix;
class TplixCompileStrpos
{
    function call(array $params = []): string
    {
        return strpos($params[0] ?? '',$params[1]);
    }
}