<?php

require_once __DIR__ . '/../Formatter/OutputFormatterInterface.php';
require_once __DIR__ . '/../Terminal.php';
require_once __DIR__ . '/../Helper/Helper.php';
require_once __DIR__ . '/StreamOutput.php';

class ConsoleSectionOutput extends StreamOutput
{
    private $content = array();
    private $lines = 0;
    private $sections;
    private $terminal;

    /**
     * @param resource $stream
     * @param ConsoleSectionOutput[] $sections
     * @param $verbosity
     * @param $decorated
     * @param OutputFormatterInterface $formatter
     */
    public function __construct($stream, array &$sections, $verbosity, $decorated, OutputFormatterInterface $formatter)
    {
        parent::__construct($stream, $verbosity, $decorated, $formatter);
        array_unshift($sections, $this);
        $this->sections = &$sections;
        $this->terminal = new Terminal();
    }

    /**
     * Overwrites the previous output with a new message.
     *
     * @param array|string $message
     */
    public function overwrite($message)
    {
        $this->clear();
        $this->writeln($message);
    }

    /**
     * Clears previous output for this section.
     *
     * @param int $lines Number of lines to clear. If null, then the entire output of this section is cleared
     */
    public function clear($lines = null)
    {
        if (empty($this->content) || !$this->isDecorated()) {
            return;
        }

        if ($lines) {
            \array_splice($this->content, -($lines * 2)); // Multiply lines by 2 to cater for each new line added between content
        } else {
            $lines = $this->lines;
            $this->content = array();
        }

        $this->lines -= $lines;

        parent::doWrite($this->popStreamContentUntilCurrentSection($lines), false);
    }

    /**
     * At initial stage, cursor is at the end of stream output. This method makes cursor crawl upwards until it hits
     * current section. Then it erases content it crawled through. Optionally, it erases part of current section too.
     *
     * @param int $numberOfLinesToClearFromCurrentSection
     *
     * @return string
     */
    private function popStreamContentUntilCurrentSection($numberOfLinesToClearFromCurrentSection = 0)
    {
        $numberOfLinesToClear = $numberOfLinesToClearFromCurrentSection;
        $erasedContent = array();

        foreach ($this->sections as $section) {
            if ($section === $this) {
                break;
            }

            $numberOfLinesToClear += $section->lines;
            $erasedContent[] = $section->getContent();
        }

        if ($numberOfLinesToClear > 0) {
            // move cursor up n lines
            parent::doWrite(sprintf("\x1b[%dA", $numberOfLinesToClear), false);
            // erase to end of screen
            parent::doWrite("\x1b[0J", false);
        }

        return implode('', array_reverse($erasedContent));
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return implode('', $this->content);
    }

    /**
     * @param string $message
     * @param bool $newline
     */
    protected function doWrite($message, $newline)
    {
        if (!$this->isDecorated()) {
            return parent::doWrite($message, $newline);
        }

        $erasedContent = $this->popStreamContentUntilCurrentSection();

        $this->addContent($message);

        parent::doWrite($message, true);
        parent::doWrite($erasedContent, false);
    }

    /**
     * @param $input
     */
    public function addContent($input)
    {
        foreach (explode(PHP_EOL, $input) as $lineContent) {
            $this->lines += ceil($this->getDisplayLength($lineContent) / $this->terminal->getWidth()) ?: 1;
            $this->content[] = $lineContent;
            $this->content[] = PHP_EOL;
        }
    }

    /**
     * @param $text
     *
     * @return int
     */
    private function getDisplayLength($text)
    {
        return Helper::strlenWithoutDecoration($this->getFormatter(), str_replace("\t", '        ', $text));
    }
}
