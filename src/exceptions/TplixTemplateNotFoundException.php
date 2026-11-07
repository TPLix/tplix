<?php
namespace Tplix;

/**
 * Exception thrown when template file is not found
 */
class TplixTemplateNotFoundException extends TplixException
{
    public function __construct($template, $message = '', $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = "Template file not found: '$template'";
        }
        parent::__construct($message, $code, $previous);
        $this->setTemplateFile($template);
    }
}