<?php

/**
 * Formatter interface for console output that supports word wrapping.
 */
interface WrappableOutputFormatterInterface extends OutputFormatterInterface
{
    /**
     * Formats a message according to the given styles, wrapping at `$width` (0 means no wrapping).
     */
    public function formatAndWrap($message, $width);
}
