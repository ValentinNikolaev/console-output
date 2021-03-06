<?php

require_once __DIR__ . '/WrappableOutputFormatterInterface.php';
require_once __DIR__ . '/OutputFormatterStyle.php';
require_once __DIR__ . '/OutputFormatterStyleStack.php';
require_once __DIR__ . '/OutputFormatterStyleInterface.php';

/**
 * Formatter class for console output.
 */
class OutputFormatter implements WrappableOutputFormatterInterface
{
    /**
     * @var bool
     */
    private $decorated;

    /**
     * @var array
     */
    private $styles = array();

    /**
     * @var OutputFormatterStyleStack
     */
    private $styleStack;

    /**
     * Escapes "<" special char in given text.
     *
     * @param string $text Text to escape
     *
     * @return string Escaped text
     */
    public static function escape($text)
    {
        $text = preg_replace('/([^\\\\]?)</', '$1\\<', $text);

        return self::escapeTrailingBackslash($text);
    }

    /**
     * Escapes trailing "\" in given text.
     *
     * @param string $text Text to escape
     *
     * @return string Escaped text
     *
     * @internal
     */
    public static function escapeTrailingBackslash($text)
    {
        if ('\\' === substr($text, -1)) {
            $len = \strlen($text);
            $text = rtrim($text, '\\');
            $text = str_replace("\0", '', $text);
            $text .= str_repeat("\0", $len - \strlen($text));
        }

        return $text;
    }

    /**
     * Initializes console output formatter.
     *
     * @param bool $decorated Whether this formatter should actually decorate strings
     * @param OutputFormatterStyleInterface[] $styles Array of "name => FormatterStyle" instances
     */
    public function __construct($decorated = false, array $styles = array())
    {
        $this->decorated = $decorated;

        $this->setStyle('error', new OutputFormatterStyle('white', 'red'));
        $this->setStyle('info', new OutputFormatterStyle('green'));
        $this->setStyle('comment', new OutputFormatterStyle('yellow'));
        $this->setStyle('question', new OutputFormatterStyle('black', 'cyan'));

        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }

        $this->styleStack = new OutputFormatterStyleStack();
    }

    /**
     * @param bool $decorated
     */
    public function setDecorated($decorated)
    {
        $this->decorated = (bool)$decorated;
    }

    /**
     * @return bool
     */
    public function isDecorated()
    {
        return $this->decorated;
    }

    /**
     * @param string $name
     *
     * @param OutputFormatterStyleInterface $style
     */
    public function setStyle($name, OutputFormatterStyleInterface $style)
    {
        $this->styles[strtolower($name)] = $style;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasStyle($name)
    {
        return isset($this->styles[strtolower($name)]);
    }

    /**
     * @param string $name
     *
     * @return mixed|OutputFormatterStyleInterface
     */
    public function getStyle($name)
    {
        if (!$this->hasStyle($name)) {
            throw new InvalidArgumentException(sprintf('Undefined style: %s', $name));
        }

        return $this->styles[strtolower($name)];
    }

    /**
     * @param string $message
     *
     * @return mixed|string
     */
    public function format($message)
    {
        return $this->formatAndWrap((string)$message, 0);
    }

    /**
     * @param $message
     * @param $width
     *
     * @return mixed|string
     */
    public function formatAndWrap($message, $width)
    {
        $offset = 0;
        $output = '';
        $tagRegex = '[a-z][^<>]*+';
        $currentLineLength = 0;
        preg_match_all("#<(($tagRegex) | /($tagRegex)?)>#ix", $message, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => $match) {
            $pos = $match[1];
            $text = $match[0];

            if (0 != $pos && '\\' == $message[$pos - 1]) {
                continue;
            }

            // add the text up to the next tag
            $output .= $this->applyCurrentStyle(substr($message, $offset, $pos - $offset), $output, $width, $currentLineLength);
            $offset = $pos + \strlen($text);

            // opening tag?
            if ($open = '/' != $text[1]) {
                $tag = $matches[1][$i][0];
            } else {
                $tag = isset($matches[3][$i][0]) ? $matches[3][$i][0] : '';
            }

            if (!$open && !$tag) {
                // </>
                $this->styleStack->pop();
            } elseif (false === $style = $this->createStyleFromString($tag)) {
                $output .= $this->applyCurrentStyle($text, $output, $width, $currentLineLength);
            } elseif ($open) {
                $this->styleStack->push($style);
            } else {
                $this->styleStack->pop($style);
            }
        }

        $output .= $this->applyCurrentStyle(substr($message, $offset), $output, $width, $currentLineLength);

        if (false !== strpos($output, "\0")) {
            return strtr($output, array("\0" => '\\', '\\<' => '<'));
        }

        return str_replace('\\<', '<', $output);
    }

    /**
     * @return OutputFormatterStyleStack
     */
    public function getStyleStack()
    {
        return $this->styleStack;
    }

    /**
     * Tries to create new style instance from string.
     *
     * @return OutputFormatterStyle|false False if string is not format string
     */
    private function createStyleFromString($string)
    {
        if (isset($this->styles[$string])) {
            return $this->styles[$string];
        }

        if (!preg_match_all('/([^=]+)=([^;]+)(;|$)/', $string, $matches, PREG_SET_ORDER)) {
            return false;
        }

        $style = new OutputFormatterStyle();
        foreach ($matches as $match) {
            array_shift($match);
            $match[0] = strtolower($match[0]);

            if ('fg' == $match[0]) {
                $style->setForeground(strtolower($match[1]));
            } elseif ('bg' == $match[0]) {
                $style->setBackground(strtolower($match[1]));
            } elseif ('href' === $match[0]) {
                $style->setHref($match[1]);
            } elseif ('options' === $match[0]) {
                preg_match_all('([^,;]+)', strtolower($match[1]), $options);
                $options = array_shift($options);
                foreach ($options as $option) {
                    $style->setOption($option);
                }
            } else {
                return false;
            }
        }

        return $style;
    }

    /**
     * Applies current style from stack to text, if must be applied.
     * @param string $text
     * @param string $current
     * @param int $width
     * @param int $currentLineLength
     *
     * @return string
     */
    private function applyCurrentStyle($text, $current, $width, &$currentLineLength)
    {
        if ('' === $text) {
            return '';
        }

        if (!$width) {
            return $this->isDecorated() ? $this->styleStack->getCurrent()->apply($text) : $text;
        }

        if (!$currentLineLength && '' !== $current) {
            $text = ltrim($text);
        }

        if ($currentLineLength) {
            $prefix = substr($text, 0, $i = $width - $currentLineLength) . "\n";
            $text = substr($text, $i);
        } else {
            $prefix = '';
        }

        preg_match('~(\\n)$~', $text, $matches);
        $text = $prefix . preg_replace('~([^\\n]{' . $width . '})\\ *~', "\$1\n", $text);
        $text = rtrim($text, "\n") . ($matches[1] ? $matches[1] : '');

        if (!$currentLineLength && '' !== $current && "\n" !== substr($current, -1)) {
            $text = "\n" . $text;
        }

        $lines = explode("\n", $text);
        if ($width === $currentLineLength = \strlen(end($lines))) {
            $currentLineLength = 0;
        }

        if ($this->isDecorated()) {
            foreach ($lines as $i => $line) {
                $lines[$i] = $this->styleStack->getCurrent()->apply($line);
            }
        }

        return implode("\n", $lines);
    }
}
