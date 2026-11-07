<?php
/**************
 * {debug}
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileDebug
{
    function call(array $params = []): string
    {
        $tplix = Tplix::getInstance();
        $vars = $tplix->getScope();
        $globals = [];
        $configs = [];
        $assigned_vars = [];
        foreach(["get","post","request","cookies","session"] as $item){
            if(isset($vars["tplVar"][$item]))
            {
                foreach ($vars["tplVar"][$item] as $k=>$v)
                {
                    $globals[$item.".".$k] = ["value"=>$v,"scope"=>"global","nocache"=>true,"attributes"=>[]];
                }
                unset($vars["tplVar"][$item]);
            }
        }
        unset($vars["tplVar"]["server"]);
        unset($vars["tplVar"]["env"]);
        if(isset($vars["tplVar"]["config"]))
        {
            foreach ($vars["tplVar"]["config"] as $k=>$v){
                $configs[$k] = ["value"=>$v,"scope"=>"global"];
            }
            unset($vars["tplVar"]["config"]);
        }
        foreach ($vars as $k=>$v){
            $assigned_vars[$k] = ["value"=>$v,"scope"=>"global"];
        }

        $IncludedTemplates = $tplix->getIncludedTemplates();
        $debug_tpl = $tplix->debug_tpl;
        
        $path = "";
        if(str_starts_with($debug_tpl,"src")) $path = str_replace("src/libs/plugins/debug.php","",__FILE__);
        $fullPath = $path.$debug_tpl;
        $debugData = [
            'version' => Tplix::TPLIX_VERSION,
            'template_name' => $fullPath,
            'globals' => $globals,
            'globals_vars_count' => count($globals),
            'assigned_vars' => $assigned_vars,
            'assigned_vars_count' => count($assigned_vars),
            'total_vars_count' => count($assigned_vars)+count($globals),
            'config_vars' => $configs,
            'template_data' => $IncludedTemplates,
            'execution_time' => microtime(true) - $tplix->startTime
        ];
        return $tplix->fetch($fullPath, true, $debugData,false);
    }
}
