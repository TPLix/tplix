<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileHtmlspecialchars
{
    public function call(array $params = []): string
    {
        $source     = $params[0] ?? '';
        $charset  = Tplix::getInstance()->charset;
        return htmlspecialchars($source, ENT_QUOTES, $charset);
    }
}