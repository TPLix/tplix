<?php
/****************
 * {3.14|round}
 * {3.141592|round:2}
 * {3.141592|round:2:1}
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;

class TplixCompileRound
{
    public static function call(array $args)
    {
        $value = $args[0] ?? 0;
        $round = $args[1] ?? 0;
        $mode  = $args[2] ?? PHP_ROUND_HALF_UP;

        return round((float)$value, (int)$round, (int)$mode);
    }
}