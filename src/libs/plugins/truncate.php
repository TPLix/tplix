<?php
/****************
 * {$articleTitle|truncate}<br>
 * {$articleTitle|truncate:30}<br>
 * {$articleTitle|truncate:30:""}<br>
 * {$articleTitle|truncate:30:"---"}<br>
 * {$articleTitle|truncate:30:"":true}<br>
 * {$articleTitle|truncate:30:"...":true}<br>
 * {$articleTitle|truncate:30:'..':true:true}
 * *************/
/**
 * Truncate a string to a given length
 *
 * @param string  $string      Input string
 * @param integer $length      Max length (default 80)
 * @param string  $etc         String to append if truncated (default '...')
 * @param boolean $break_words If false, truncate at word boundary (default false)
 * @param boolean $middle      If true, truncate in the middle (default false)
 * @return string
 */ 
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileTruncate
{
    public static function call(array $args)
    {
        $encoding = "UTF-8";
        [$string,$length,$etc,$break_words,$middle] = $args;
        $length = (int)$length>0 ? $length:80;
        $etc = $etc ?? '...';
        $break_words = $break_words ?? false;
        $middle = $middle ?? false;
        if ($length === 0) return '';
    
        if (mb_strlen($string,$encoding) > $length) {
            $length -= mb_strlen($etc,$encoding);
    
            if (!$break_words && !$middle) {
                // Find last space within limit
                $pos = mb_strrpos(mb_substr($string, 0, $length,$encoding), ' ', 0,$encoding);
                if ($pos !== false) {
                    $string = mb_substr($string, 0, $pos,$encoding);
                } else {
                    $string = mb_substr($string, 0, $length,$encoding);
                }
            } elseif ($middle) {
                $start = ceil($length / 2);
                $end   = floor($length / 2);
                $string = mb_substr($string, 0, $start,$encoding) . $etc . mb_substr($string, -$end, null,$encoding);
            } else {
                $string = mb_substr($string, 0, $length,$encoding) . $etc;
            }
        }
        return $string;
    }
}
