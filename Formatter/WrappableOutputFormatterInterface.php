<?php

require_once __DIR__.'/OutputFormatterInterface.php';

/**
 * Formatter interface for console output that supports word wrapping.
 */
interface WrappableOutputFormatterInterface extends OutputFormatterInterface
{
    /**
     * Formats a message according to the given styles, wrapping at `$width` (0 means no wrapping).
     * @param $message
     * @param $width
     */
    public function formatAndWrap($message, $width);
}
