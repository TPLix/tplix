<?php
namespace Tplix;
/**
 * Exception thrown when template execution fails
 */
class TplixExecutionException extends TplixException
{
    public function __construct($message = '', $template = '', $line = 0, $context = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if ($template) {
            $this->setTemplateFile($template);
        }
        if ($line) {
            $this->setLineNumber($line);
        }
        if ($context) {
            $this->setSourceContext($context);
        }
    }
}
