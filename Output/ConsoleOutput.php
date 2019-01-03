<?php

require_once __DIR__ . '/../Formatter/OutputFormatterInterface.php';
require_once __DIR__ . '/StreamOutput.php';
require_once __DIR__ . '/ConsoleOutputInterface.php';

/**
 * ConsoleOutput is the default class for all CLI output. It uses STDOUT and STDERR.
 *
 * This class is a convenient wrapper around `StreamOutput` for both STDOUT and STDERR.
 *
 *     $output = new ConsoleOutput();
 *
 * This is equivalent to:
 *
 *     $output = new StreamOutput(fopen('php://stdout', 'w'));
 *     $stdErr = new StreamOutput(fopen('php://stderr', 'w'));
 *
 */
class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private $stderr;
    private $consoleSectionOutputs = array();

    /**
     * @param int $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool|null $decorated Whether to decorate messages (null for auto-guessing)
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     */
    public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
    {
        parent::__construct($this->openOutputStream(), $verbosity, $decorated, $formatter);

        $actualDecorated = $this->isDecorated();
        $this->stderr = new StreamOutput($this->openErrorStream(), $verbosity, $decorated, $this->getFormatter());

        if (null === $decorated) {
            $this->setDecorated($actualDecorated && $this->stderr->isDecorated());
        }
    }

    /**
     * @return resource
     */
    private function openOutputStream()
    {
        if (!$this->hasStdoutSupport()) {
            return fopen('php://output', 'w');
        }

        return @fopen('php://stdout', 'w') ?: fopen('php://output', 'w');
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     *
     * @return bool
     */
    protected function hasStdoutSupport()
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Checks if current executing environment is IBM iSeries (OS400), which
     * doesn't properly convert character-encodings between ASCII to EBCDIC.
     *
     * @return bool
     */
    private function isRunningOS400()
    {
        $checks = array(
            \function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            PHP_OS,
        );

        return false !== stripos(implode(';', $checks), 'OS400');
    }

    /**
     * @return resource
     */
    private function openErrorStream()
    {
        return fopen($this->hasStderrSupport() ? 'php://stderr' : 'php://output', 'w');
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     *
     * @return bool
     */
    protected function hasStderrSupport()
    {
        return false === $this->isRunningOS400();
    }

    /**
     * @param bool $decorated
     */
    public function setDecorated($decorated)
    {
        parent::setDecorated($decorated);
        $this->stderr->setDecorated($decorated);
    }

    /**
     * Creates a new output section.
     *
     * @return ConsoleSectionOutput
     */
    public function section()
    {
        return new ConsoleSectionOutput($this->getStream(), $this->consoleSectionOutputs, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }

    /**
     * @param OutputFormatterInterface $formatter
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        $this->stderr->setFormatter($formatter);
    }

    /**
     * @param int $level
     */
    public function setVerbosity($level)
    {
        parent::setVerbosity($level);
        $this->stderr->setVerbosity($level);
    }

    /**
     * @return OutputInterface|StreamOutput
     */
    public function getErrorOutput()
    {
        return $this->stderr;
    }

    /**
     * @param OutputInterface $error
     */
    public function setErrorOutput(OutputInterface $error)
    {
        $this->stderr = $error;
    }
}
