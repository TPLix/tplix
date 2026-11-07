<?php
use Tplix\Tplix;
class TplixCompileInclude
{
    private static array $includeStack = [];
    public function call(array $args = []): string
    {
        $params = $args["params"] ?? $args;
        $iFile   = $params["file"] ?? null;
        if (!$iFile) return '';
        //$iFile = is_array($iFile) ? implode("",$iFile):$iFile;
        $tplix = Tplix::getInstance();
        $baseScope = $tplix->getScope();
        $path  = rtrim($tplix->templateDir ?? '', '/');
        $ofile = $path . '/' . ltrim($iFile, '/');
        if(file_exists($ofile))
        {
            $file = $ofile;
        }else{
            if(file_exists($iFile))
            {
                $file = $iFile;
            }else{
                return "";
            }
        }
        // prevent circular include
        if (isset(self::$includeStack[$file])) {
            return "<!-- Skipped recursive include: {$file} -->";
        }
        self::$includeStack[$file] = true;
        // remove reserved keys before merging
        unset($params["file"], $params["scope"], $params["modifiers"]);
        $mergedScope = array_merge($baseScope, $params);
        // render included template
        $content = $tplix->fetch($file, true, $mergedScope,false);
        //array_pop(self::$includeStack);
        unset(self::$includeStack[$file]);
        return $content;
    }
}
