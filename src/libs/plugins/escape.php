<?php
use Tplix\Tplix;

class TplixCompileEscape
{
    public function call(array $args = []): string
    {
        $value = $args[0];
        $format = isset($args[1]) ? $args[1]:"html";
        $charset = isset($args[2]) ? $args[2]:Tplix::getInstance()->charset;

        switch ($format) {
            case 'html':
                return htmlspecialchars((string)$value, ENT_QUOTES, $charset);
            case 'htmlall':
                return htmlentities((string)$value, ENT_QUOTES, $charset);
            case 'url':
                return rawurlencode((string)$value);
            case 'urlpathinfo':
                return str_replace('%2F', '/', rawurlencode((string)$value));
            case 'quotes':
                return preg_replace("#(?<!\\\\)'#", "\\'", (string)$value);
            case 'hex':
                $out = '';
                $cnt = strlen((string)$value);
                for ($i = 0; $i < $cnt; ++ $i) {
                    $out .= '%' . bin2hex((string)$value[$i]);
                }

                return $out;
            case 'hexentity':
                $out = '';
                $cnt = strlen((string)$value);
                for ($i = 0; $i < $cnt; ++ $i) {
                    $out .= '&#x' . bin2hex((string)$value[$i]) . ';';
                }

                return $out;
            case 'javascript':
            case 'js':
                return strtr((string)$value,
                    array(
                        '\\' => '\\\\',
                        "'"  => "\\'",
                        '"'  => '\\"',
                        "\r" => '\\r',
                        "\n" => '\\n',
                        '</' => '<\/'
                    ));
            case 'mail':
                return str_replace(array(
                    '@',
                    '.'
                ),
                    array(
                        '&nbsp;(AT)&nbsp;',
                        '&nbsp;(DOT)&nbsp;'
                    ),
                    (string)$value);
            default:
                throw new Exception("Escape's format argument must be one of : html, htmlall, url, urlpathinfo, hex, hexentity, javascript, js or mail, ".$format." given.",
                    E_USER_WARNING);
        }
    }
}