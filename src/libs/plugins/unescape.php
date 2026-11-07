<?php
/****************
 * {$articleTitle|unescape:"html"}<br>
 * {$articleTitle|unescape:"htmlall"}
 * *************/
/**
 * Purpose:  Decode escaped strings (HTML, URL, hex, etc.)
 *
 * @param string $string      Input string
 * @param string $esc_type    Escape type: 'html', 'htmlall', 'url', 'hex', 'unicode'
 * @param string $charset     Character set (used in some modes)
 * @return string
 */ 
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileUnescape
{
    public static function call(array $args)
    {
        [$string,$esc_type,$charset] = $args;
        $charset = $charset ?? 'ISO-8859-1';
        $esc_type = $esc_type ?? 'html';
        switch ($esc_type) {
            case 'entity':
            case 'htmlall':
                // Convert all HTML entities (including numerical)
                return htmlspecialchars_decode($string, ENT_QUOTES | ENT_HTML5);
    
            case 'html':
                // Convert basic HTML entities: &amp;, <, >, &quot;, &#039;
                return htmlspecialchars_decode($string, ENT_QUOTES);
    
            case 'url':
                // Decode URL-encoded string
                return rawurldecode($string);
    
            case 'hex':
                // Convert hex escapes like \x48\x65\x6C\x6C\x6F → "Hello"
                return preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/', function($matches) {
                    return chr(hexdec($matches[1]));
                }, $string);
    
            case 'unicode':
                // Convert \uXXXX escapes (basic support)
                return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($matches) {
                    $code = hexdec($matches[1]);
                    if ($code < 0x80) {
                        return chr($code);
                    } elseif ($code < 0x800) {
                        return chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
                    } elseif ($code < 0xD800 || $code > 0xDFFF) {
                        return chr(0xE0 | ($code >> 12)) .
                               chr(0x80 | (($code >> 6) & 0x3F)) .
                               chr(0x80 | ($code & 0x3F));
                    }
                    return ''; // surrogate pairs not handled
                }, $string);
    
            default:
                return $string;
        }
    }
}
