<?php
/****************
 * {$articleTitle}<br>
 * {$articleTitle|wordwrap:30}<br>
 * {$articleTitle|wordwrap:20}<br>
 * {$articleTitle|wordwrap:30:"<br />\n"}<br>
 * {$articleTitle|wordwrap:26:"\n":true}
 * *************/
/**
 * Wraps a string to a given number of characters
 *
 * @param string  $string      Input string
 * @param integer $length      Max line length (default: 80)
 * @param string  $break       Line break string (default: "\n")
 * @param boolean $cut         If true, cut words longer than $length (default: false)
 * @return string
 */
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileWordwrap
{
    public static function call(array $args)
    {
        $encoding = "UTF-8";
        [$string,$length,$break_words,$cut] = $args;
        $length = (int)$length>0 ? $length:80;
        $break = $break ?? "\n";
        $cut = $cut ?? false;
        // Simple fallback: use PHP wordwrap for ASCII, or warn for Unicode
        // For full UTF-8 support, a more complex algorithm is needed
        if (!mb_check_encoding($string,$encoding)) {
            return wordwrap($string, $length, $break, $cut);
        }
    
        // Basic UTF-8 wordwrap (simplified — doesn't handle all word boundaries)
        $result = '';
        $line = '';
        $words = preg_split('/(\s+)/u', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
    
        foreach ($words as $word) {
            if (mb_strlen($line . $word,$encoding) <= $length) {
                $line .= $word;
            } else {
                if ($line !== '') {
                    $result .= $line . $break;
                }
                if ($cut && mb_strlen($word,$encoding) > $length) {
                    // Force cut long word
                    while (mb_strlen($word,$encoding) > $length) {
                        $result .= mb_substr($word, 0, $length,$encoding) . $break;
                        $word = mb_substr($word, $length, null,$encoding);
                    }
                    $line = $word;
                } else {
                    $line = $word;
                }
            }
        }
        $result .= $line;
        return $result;
    }
}
