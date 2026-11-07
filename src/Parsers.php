<?php
namespace Tplix;
class Parsers
{
    private static array $parsers = [];
    /**
     * Load all parsers from a folder
     */
    public static function load(): void
    {
        $folder = __DIR__.'/libs/parse';
        foreach (glob($folder."/*.php") as $file) {
            include_once $file;
        }
    }

    /**
     * Register a parser manually
     */
    public static function registerParser(string $name, callable $callback): void
    {
        self::$parsers[$name] = $callback;
    }
    /**
     * Parser exist or not
     */
    public static function exist(string $name=""): bool
    {
        if(empty($name)) return false;
        $name = strtolower($name);
        $parser = __DIR__.'/libs/parse/'.$name.'.php';
        return file_exists($parser);
    }

    /**
     * Call a parser by name
     */
    public static function call(string $parserName, $args = ""): mixed
    {
        $parser = __DIR__.'/libs/parse/'.$parserName.'.php';
        include_once($parser);
        // Build class name dynamically
        $className = "TplixParse" . ucfirst($parserName);
    
        if (!class_exists($className)) {
            throw new Exception("Parser class '$className' does not exist");
        }
        $parser = new $className();
        if (!method_exists($parser, 'parse')) {
            throw new Exception("Parser class '$className' does not have a 'parse' method");
        }
        // Determine whether to pass FOREACH_ID based on method signature
        //$reflection = new ReflectionMethod($plugin, 'parse');
        //$params = $reflection->getNumberOfParameters();
        return $parser->parse($args);
    }
}
