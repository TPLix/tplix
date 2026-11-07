<?php
namespace Tplix;
/**
 * Exception thrown when plugin or modifier is not found
 */
class TplixPluginNotFoundException extends TplixException
{
    public function __construct($pluginName, $type = 'plugin', $message = '', $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = ucfirst($type) . " '$pluginName' not found";
        }
        parent::__construct($message, $code, $previous);
    }
}