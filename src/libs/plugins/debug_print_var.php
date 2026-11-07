<?php
/**************
 * {debug}
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileDebug_print_var
{
    function call(array $params = []): string
    {
        $var = $params[0];
        $max = isset($params[1]) ? $params[1]:10;
        $length = isset($params[2]) ? $params[2]:40;
        $depth = isset($params[3]) ? $params[3]:0;
        return $this->process($var,$max,$length,$depth);
    }
    function process($var, $max = 10, $length = 40, $depth = 0, $objects = []){
        $_replace = array("\n" => '\n', "\r" => '\r', "\t" => '\t');
        switch (gettype($var)) {
            case 'array':
                $results = '<b>Array (' . count($var) . ')</b>';
                if ($depth === $max) {
                    break;
                }
                foreach ($var as $curr_key => $curr_val) {
                    $results .= '<br>' . str_repeat('&nbsp;', $depth * 2) . '<b>' . strtr($curr_key, $_replace) .
                                '</b> =&gt; ' .
                                $this->process($curr_val, $max, $length, ++$depth, $objects);
                    $depth--;
                }
                break;
            case 'object':
                $object_vars = get_object_vars($var);
                $results = '<b>' . get_class($var) . ' Object (' . count($object_vars) . ')</b>';
                if (in_array($var, $objects)) {
                    $results .= ' called recursive';
                    break;
                }
                if ($depth === $max) {
                    break;
                }
                $objects[] = $var;
                foreach ($object_vars as $curr_key => $curr_val) {
                    $results .= '<br>' . str_repeat('&nbsp;', $depth * 2) . '<b> -&gt;' . strtr($curr_key, $_replace) .
                                '</b> = ' . $this->process($curr_val, $max, $length, ++$depth, $objects);
                    $depth--;
                }
                break;
            case 'boolean':
            case 'NULL':
            case 'resource':
                if (true === $var) {
                    $results = 'true';
                } elseif (false === $var) {
                    $results = 'false';
                } elseif (null === $var) {
                    $results = 'null';
                } else {
                    $results = htmlspecialchars((string)$var);
                }
                $results = '<i>' . $results . '</i>';
                break;
            case 'integer':
            case 'float':
                $results = htmlspecialchars((string)$var);
                break;
            case 'string':
                $results = strtr($var, $_replace);
                if (Tplix::$_MBSTRING) {
                    if (mb_strlen($var, Tplix::$_charset) > $length) {
                        $results = mb_substr($var, 0, $length - 3, Tplix::$_charset) . '...';
                    }
                } else {
                    if (isset($var[ $length ])) {
                        $results = substr($var, 0, $length - 3) . '...';
                    }
                }
                $results = htmlspecialchars('"' . $results . '"', ENT_QUOTES, Tplix::$_charset);
                break;
            case 'unknown type':
            default:
                $results = strtr((string)$var, $_replace);
                if (Tplix::$_MBSTRING) {
                    if (mb_strlen($results, Tplix::$_charset) > $length) {
                        $results = mb_substr($results, 0, $length - 3, Tplix::$_charset) . '...';
                    }
                } else {
                    if (strlen($results) > $length) {
                        $results = substr($results, 0, $length - 3) . '...';
                    }
                }
                $results = htmlspecialchars($results, ENT_QUOTES, Tplix::$_charset);
        }
        return $results;        
    }
}