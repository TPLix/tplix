<?php
namespace Tplix;
use Tplix\Plugins;
class Custom
{
    static function arrayToPhpCode($value): string
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $k => $v) {
                $key = is_int($k) ? '' : self::arrayToPhpCode($k) . ' => ';
                $items[] = $key . self::arrayToPhpCode($v);
            }
            return '[' . implode(', ', $items) . ']';
        }
        if (is_string($value)) {
            $value = trim($value);
            // already quoted string
            if (preg_match('/^(["\']).*\\1$/', $value)) return $value;
            // boolean strings
            $lower = strtolower($value);
            if ($lower === 'true' || $lower === 'false') return $lower;
            // numeric string
            if (is_numeric($value)) return $value;
            // php expression detection ($var, function(), concatenation)
            if (preg_match('/^\$|->|::|\(/', $value)) return rtrim($value, ';');
            return var_export($value, true);
        }
        if (is_bool($value)) return $value ? 'true' : 'false';
        if ($value === null) return 'null';
        return (string)$value;
    }
}