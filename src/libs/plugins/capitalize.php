<?php
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileCapitalize
{
    public function call(array $params = []): string
    {
        $value     = $params[0] ?? '';      // e.g., $Name
        $numwords = $params[1] ?? 0;
        $charset  = Tplix::getInstance()->charset;
        if ($numwords)
        {
            return mb_convert_case((string)$value, MB_CASE_TITLE,$charset);
        } else {
            $bits = explode(' ', (string)$value);
            $out  = '';
            foreach ($bits as $k => $v){
                if (preg_match('#^[^0-9]+$#', $v)) {
                    $out .= ' ' . mb_convert_case($v, MB_CASE_TITLE, $charset);
                } else {
                    $out .= ' ' . $v;
                }
            }
            return substr($out, 1);
        }
    }
}