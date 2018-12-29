<?php
require_once __DIR__.'/OutputInterface.php';

/**
 * ConsoleOutputInterface is the interface implemented by ConsoleOutput class.
 * This adds information about stderr and section output stream.
 *
 * @method ConsoleSectionOutput section() Creates a new output section
 */
interface ConsoleOutputInterface extends OutputInterface
{
    /**
     * Gets the OutputInterface for errors.
     *
     * @return OutputInterface
     */
    public function getErrorOutput();

    public function setErrorOutput(OutputInterface $error);
}
