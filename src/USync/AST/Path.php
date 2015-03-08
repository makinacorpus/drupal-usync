<?php

namespace USync\AST;

class Path
{
    const SEP = '.';

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string[]
     */
    protected $segments;

    /**
     * @var int
     */
    protected $current = 0;

    /**
     * Default constructor
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->segments = explode(self::SEP, $path);
    }

    public function next()
    {
        ++$this->current;

        return $this->current < count($this->segments);
    }

    public function matches(Node $node)
    {
        // @todo While node parent
        throw new \Exception("Not implemented");
    }

    public function getCurrent()
    {
        return $this->segments[$this->current];
    }

    public function matchesCurrent($key)
    {
        return '%' === $this->segments[$this->current] || $key === $this->segments[$this->current];
    }
}