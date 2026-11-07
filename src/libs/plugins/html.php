<?php
use Tplix\Tplix;
class TplixCompileHtml
{
    function call(array $params = []): string
    {
        $text = $params[0];
        if($text === null) return "";
        return html_entity_decode($text);
    }
}