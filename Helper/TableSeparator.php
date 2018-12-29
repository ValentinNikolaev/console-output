<?php

require_once __DIR__.'/TableCell.php';

/**
 * Marks a row as being a separator.
 */
class TableSeparator extends TableCell
{
    public function __construct(array $options = array())
    {
        parent::__construct('', $options);
    }
}
