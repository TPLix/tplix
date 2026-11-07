<?php
/**************
 * {a $url class="external" title="Tplix" target="_blank"}Tplix{/a}
 * {a $url class="external"}Tplix{/a}
 * {a $url title="oks" /}<br>
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileA
{
    function call(array $params = []): string
    {
        if($params["type"] == "A_END") return "</a>";

        $value = isset($params["params"][0]) ? $params["params"][0] : null;
        $class = isset($params["params"]["class"]) ? $params["params"]["class"] : null;
        $title = isset($params["params"]["title"]) ? $params["params"]["title"] : null;
        $target = isset($params["params"]["target"]) ? $params["params"]["target"] : "";
        $selfClosing = isset($params["params"]["selfClosing"]) ? $params["params"]["selfClosing"] : 0;
        // If $value starts with $, treat as variable
        if ($value && $value[0] === '$')
        {
            $value = Tplix::compileVariableExpression($value);
            $href = $value;
        } else {
            $href = $value;
        }
        $title = !empty($title) ? $title : $href;
        $output = '<a href="' . $href . '" ';
        if (!empty($class)) $output .= 'class="' . $class . '" ';
        if (!empty($title)) $output .= 'title="'.$title.'"';
        if (!empty($target)) $output .= 'target="' . $target . '"';
        $output .= $selfClosing ? '>' . $title . '</a>': '>';
        return $output;
    }
}
