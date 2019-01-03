<?php

class TableCell
{
    private $value;
    private $options = array(
        'rowspan' => 1,
        'colspan' => 1,
    );

    public function __construct($value = '', array $options = array())
    {
        $this->value = $value;

        // check option names
        if ($diff = array_diff(array_keys($options), array_keys($this->options))) {
            throw new InvalidArgumentException(sprintf('The TableCell does not support the following options: \'%s\'.', implode('\', \'', $diff)));
        }

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Returns the cell value.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Gets number of colspan.
     *
     * @return int
     */
    public function getColspan()
    {
        return (int)$this->options['colspan'];
    }

    /**
     * Gets number of rowspan.
     *
     * @return int
     */
    public function getRowspan()
    {
        return (int)$this->options['rowspan'];
    }
}
