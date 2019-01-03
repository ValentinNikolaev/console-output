<?php

require_once __DIR__ . '/Output.php';

/**
 * Class BufferedOutput
 */
class BufferedOutput extends Output
{
    private $buffer = '';

    /**
     * Empties buffer and returns its content.
     *
     * @return string
     */
    public function fetch()
    {
        $content = $this->buffer;
        $this->buffer = '';

        return $content;
    }

    /**
     * @param string $message
     * @param bool $newline
     */
    protected function doWrite($message, $newline)
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= PHP_EOL;
        }
    }
}
