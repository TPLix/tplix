<?php
use Tplix\Tplix;

class TplixCompileCapture
{
    public function call(array $params = []): string
    {
        $name = $params[0];
        $tplix = Tplix::getInstance();
        // Access your global tplVars array
        $globals = $tplix->getTemplateVars(); // Direct access to your global array
        // Get the capture callable
        $capture_callable = $globals["tplVar"]['capture'][$name] ?? null;
        if (is_callable($capture_callable)){
            return call_user_func($capture_callable, $globals);
        } else {
            return $capture_callable ?? '';
        }
    }
}