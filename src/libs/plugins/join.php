<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileJoin
{
    public function call(array $params = []): string
    {
        $value     = $params[0] ?? [];      // e.g., $Name
        $implode = $params[1] ?? '';
        return implode($implode,$value);
    }
}