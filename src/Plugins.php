<?php
namespace Tplix;
use Tplix\Security;
class Plugins
{
    private static array $plugins = [];

    /**
     * Load all plugins from a folder
     */
    public static function load(): void
    {
        $folder = __DIR__.'/libs/plugins';
        foreach (glob($folder."/*.php") as $file) {
            include_once $file;
        }
    }
    /**
     * Register a plugin manually
     */
    public static function registerPlugin(string $type = "", string $name = "", $function = ""): void
    {
        if ($name === "") {
            throw new TplixPluginNotFoundException($pluginName,"plugin","Register Plugin tag empty");
        }
        if ($function === "") {
            throw new TplixPluginNotFoundException($pluginName,"plugin","Register Plugin function empty");
        }
    
        $name = strtolower($name);
    
        if (isset(self::$plugins[$name])) {
            throw new TplixPluginNotFoundException($pluginName,"plugin","Register Plugin tag '{$name}' already registered");
        }
    
        // Validate function
        if (is_string($function)) {
            if (!function_exists($function)) {
                throw new TplixPluginNotFoundException($pluginName,"plugin","Register Plugin tag '{$name}', function not exist in your code");
            }
        } elseif (!is_callable($function)) {
            throw new TplixPluginNotFoundException($pluginName,"plugin","Register Plugin '{$name}' not callable");
        }
        self::$plugins[$name] = $function;
    }
    /**
     * Call a plugin by name
     */

    public static function callableFunctionCall($name="",$args=[])
    {
        if(isset(self::$plugins[$name]))
        {
            $function = self::$plugins[$name];
            if(is_array($args))
            {
                try {
                    $ref = new \ReflectionFunction($function);
                    $requiredParams = $ref->getNumberOfParameters();
                    $values = array_values($args);
                    $args = array_slice($values, 0, $requiredParams);
                    return $function(...$args);
                } catch (ReflectionException $e)
                {
                    throw new TplixPluginNotFoundException($function,"plugin","Plugin '{$function}' not exit");
                }
            }
        }
    }
/**
 * Safely call a Tplix plugin class
 *
 * @param string $pluginName   Name of the plugin (e.g., 'foreach', 'variable')
 * @param array  $args         Arguments to pass to the call() method
 * @param int|null $FOREACH_ID Optional ID for foreach-like plugins
 *
 * @return string
 * @throws Exception if class or method does not exist
 */
    public static function call(string $pluginName, array $args = [], ?int $FOREACH_ID = null): mixed
    {
        $lowerFunName = strtolower($pluginName);
        if(isset(self::$plugins[$lowerFunName])) return self::callableFunctionCall($lowerFunName,$args);
        $plugin = __DIR__.'/libs/plugins/'.$lowerFunName.'.php';
        if(!file_exists($plugin))
        {
            $allowed =  Security::isAllowedFuncs($lowerFunName);
            if(!$allowed)
            {
                throw new TplixPluginNotFoundException($pluginName,"plugin","Plugin or function not allowed in security - Name: ".$pluginName);
            }else{
                if(function_exists($lowerFunName))
                {
                    return self::callableFunctionCall($lowerFunName,$args);
                }else{
                    throw new TplixPluginNotFoundException($pluginName,"plugin",var_exports($allowed,true)."Plugin file not found - ".$pluginName);                    
                }
            }
        }
        include_once($plugin);
        // Build class name dynamically
        $className = "TplixCompile" . ucfirst($pluginName);
        if (!class_exists($className)) throw new TplixPluginNotFoundException($pluginName,"plugin","Plugin class '$className' does not exist");
        $plugin = new $className();
        if (!method_exists($plugin, 'call')) throw new TplixPluginNotFoundException($pluginName,"plugin","Plugin class '$className' does not have a 'call' method");

        // Determine whether to pass FOREACH_ID based on method signature
        $reflection = new \ReflectionMethod($plugin, 'call');
        $params = $reflection->getNumberOfParameters();
        if ($params === 2 && $FOREACH_ID !== null) return $plugin->call($args, $FOREACH_ID);
        return $plugin->call($args);
    }
}