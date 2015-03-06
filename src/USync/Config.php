<?php

namespace USync;

class Config implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Separator used for sections.
     */
    const SECTION_SEPARATOR = '.';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var array
     */
    protected $data;

    /**
     * Default constructor
     *
     * @param array $data
     */
    public function __construct(array $data, Context $context = null)
    {
        $this->data = $data;

        if (null === $context) {
            $this->context = new Context();
        } else {
            $this->context = $context;
        }
    }

    /**
     * Get context
     *
     * @return \USync\Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get configuration for the given section.
     *
     * @param string $name
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     *   If section does not exists
     */
    public function getSection($name)
    {
        $current = &$this->data;
        foreach (explode(self::SECTION_SEPARATOR, $name) as $part) {
            if (isset($current[$part])) {
                $current = &$current[$part];
            } else {
                throw new \InvalidArgumentException(sprintf("%s could not be found in config", $name));
            }
        }

        // Keep non-data related internal properties
        $ret = clone $this;
        $ret->data = $current;

        return $ret;
    }

    /**
     * Get the current section as an array of objects
     *
     * @return array[]
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Merge the source key over the target key without overriding source keys
     *
     * Note that this only works for top level elements
     *
     * @param string $source
     * @param string $target
     */
    public function mergeOver($target, $source)
    {
        if (empty($this->data[$source])) {
            return;
        }
        if (empty($this->data[$target])) {
            $this->data[$target] = $this->data[$source];
            return;
        }
        $this->data[$target] = drupal_array_merge_deep($this->data[$source], $this->data[$target]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function count()
    {
        return count($this->data);
    }
}
