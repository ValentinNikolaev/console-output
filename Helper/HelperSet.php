<?php

require_once __DIR__ . '/Helper.php';

/**
 * HelperSet represents a set of helpers to be used with a command.
 */
class HelperSet implements \IteratorAggregate
{
    /**
     * @var Helper[]
     */
    private $helpers = array();

    /**
     * @param Helper[] $helpers An array of helper
     */
    public function __construct(array $helpers = array())
    {
        foreach ($helpers as $alias => $helper) {
            $this->set($helper, \is_int($alias) ? null : $alias);
        }
    }

    /**
     * Sets a helper.
     *
     * @param HelperInterface $helper The helper instance
     * @param string $alias An alias
     */
    public function set(HelperInterface $helper, $alias = null)
    {
        $this->helpers[$helper->getName()] = $helper;
        if (null !== $alias) {
            $this->helpers[$alias] = $helper;
        }

        $helper->setHelperSet($this);
    }

    /**
     * Gets a helper value.
     *
     * @param string $name The helper name
     *
     * @return HelperInterface The helper instance
     *
     * @throws InvalidArgumentException if the helper is not defined
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('The helper "%s" is not defined.', $name));
        }

        return $this->helpers[$name];
    }

    /**
     * Returns true if the helper if defined.
     *
     * @param string $name The helper name
     *
     * @return bool true if the helper is defined, false otherwise
     */
    public function has($name)
    {
        return isset($this->helpers[$name]);
    }

    /**
     * @return ArrayIterator|Helper[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->helpers);
    }
}
