<?php
namespace Tplix;

/**
 * Base Tplix Exception class
 */
class TplixException extends \Exception
{
    /**
     * @var string The template file where the error occurred
     */
    protected $templateFile = '';
    
    /**
     * @var int The line number where the error occurred
     */
    protected $lineNumber = 0;
    
    /**
     * @var string The source code context
     */
    protected $sourceContext = '';
    
    /**
     * Set the template file where the error occurred
     *
     * @param string $file
     * @return $this
     */
    public function setTemplateFile($file)
    {
        $this->templateFile = $file;
        return $this;
    }
    
    /**
     * Get the template file where the error occurred
     *
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }
    
    /**
     * Set the line number where the error occurred
     *
     * @param int $line
     * @return $this
     */
    public function setLineNumber($line)
    {
        $this->lineNumber = (int)$line;
        return $this;
    }
    
    /**
     * Get the line number where the error occurred
     *
     * @return int
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }
    
    /**
     * Set the source context
     *
     * @param string $context
     * @return $this
     */
    public function setSourceContext($context)
    {
        $this->sourceContext = $context;
        return $this;
    }
    
    /**
     * Get the source context
     *
     * @return string
     */
    public function getSourceContext()
    {
        return $this->sourceContext;
    }
    
    /**
     * Get a formatted error message with context
     *
     * @return string
     */
    public function getFormattedMessage()
    {
        $message = $this->getMessage();
        
        if ($this->templateFile) {
            $message .= " in template '{$this->templateFile}'";
        }
        
        if ($this->lineNumber > 0) {
            $message .= " on line {$this->lineNumber}";
        }
        
        if ($this->sourceContext) {
            $message .= "\nContext: {$this->sourceContext}";
        }
        
        return $message;
    }
    
    /**
     * Get error details as array
     *
     * @return array
     */
    public function getErrorDetails()
    {
        return [
            'message' => $this->getMessage(),
            'file' => $this->templateFile,
            'line' => $this->lineNumber,
            'context' => $this->sourceContext,
            'code' => $this->getCode(),
            'trace' => $this->getTraceAsString()
        ];
    }
}