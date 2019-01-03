<?php

require_once __DIR__ . '/../Formatter/OutputFormatterInterface.php';
require_once __DIR__ . '/HelperInterface.php';

/**
 * Helper is the base class for all helper classes.
 */
abstract class Helper implements HelperInterface
{
    /**
     * @var HelperSet|null
     */
    protected $helperSet = null;

    /**
     * Returns the subset of a string, using mb_substr if it is available.
     *
     * @param string $string String to subset
     * @param int $from Start offset
     * @param int|null $length Length to read
     *
     * @return string The string subset
     */
    public static function substr($string, $from, $length = null)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return substr($string, $from, $length);
        }

        return mb_substr($string, $from, $length, $encoding);
    }

    /**
     * @param $secs
     *
     * @return string
     */
    public static function formatTime($secs)
    {
        static $timeFormats = array(
            array(0, '< 1 sec'),
            array(1, '1 sec'),
            array(2, 'secs', 1),
            array(60, '1 min'),
            array(120, 'mins', 60),
            array(3600, '1 hr'),
            array(7200, 'hrs', 3600),
            array(86400, '1 day'),
            array(172800, 'days', 86400),
        );

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index == \count($timeFormats) - 1
                ) {
                    if (2 == \count($format)) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]) . ' ' . $format[1];
                }
            }
        }
    }

    /**
     * @param $memory
     *
     * @return string
     */
    public static function formatMemory($memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * @param OutputFormatterInterface $formatter
     * @param $string
     *
     * @return int
     */
    public static function strlenWithoutDecoration(OutputFormatterInterface $formatter, $string)
    {
        return self::strlen(self::removeDecoration($formatter, $string));
    }

    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @param string $string The string to check its length
     *
     * @return int The length of the string
     */
    public static function strlen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return \strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * @param OutputFormatterInterface $formatter
     * @param $string
     *
     * @return string|string[]|null
     */
    public static function removeDecoration(OutputFormatterInterface $formatter, $string)
    {
        $isDecorated = $formatter->isDecorated();
        $formatter->setDecorated(false);
        // remove <...> formatting
        $string = $formatter->format($string);
        // remove already formatted characters
        $string = preg_replace("/\033\[[^m]*m/", '', $string);
        $formatter->setDecorated($isDecorated);

        return $string;
    }

    /**
     * @return HelperSet|null
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * @param HelperSet|null $helperSet
     */
    public function setHelperSet(HelperSet $helperSet = null)
    {
        $this->helperSet = $helperSet;
    }
}
