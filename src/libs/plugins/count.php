<?php
/****************
 * {$var|count}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCount
{
    public function call(array $args = []): string
    {
        $value = $args[0] ?? null;
        if($value === null) return 0;
        return count($value);
    }
}