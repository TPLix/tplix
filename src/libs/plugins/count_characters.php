<?php
/****************
 * {$articleTitle|count_characters}
 * {$articleTitle|count_characters:true} true or false means spaces count or not
 * *************/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCount_characters
{
    public function call(array $args = []): int
    {
        static $charset = null;
        if ($charset === null) {
            $charset = Tplix::getInstance()->charset;
        }
        $value = (string)($args[0] ?? '');
        $countSpaces = ($args[1] ?? false) === true || ($args[1] ?? '') === 'true';

        if ($countSpaces) {
            return $charset === 'UTF-8'
                ? mb_strlen($value, $charset)
                : strlen($value);
        }

        preg_match_all('/\S/u', $value, $m);
        return count($m[0]);
    }
}