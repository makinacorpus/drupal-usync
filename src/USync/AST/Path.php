<?php

namespace USync\AST;

class Path
{
    /**
     * Path separator
     */
    const SEP = '.';

    /**
     * Match all wildcard
     */
    const WILDCARD = '%';

    /**
     * Attribute name wildcard
     */
    const MATCH = '?';

    /**
     * Does the given path matches the given pattern
     *
     * @param string $path
     * @param string $pattern
     * @param boolean $partial
     *
     * @return string[]
     *   Matched attributes. Warning the entry might be an empty array, if
     *   path does not match at all, strict false will be returned instead
     */
    static public function match($path, $pattern, $partial = false)
    {
        $attributes = [];

        $parts      = explode(self::SEP, $path);
        $segments   = explode(self::SEP, $pattern);
        $length     = count($parts);

        if ($length !== count($segments)) {
            return false;
        }

        for ($i = 0; $i < $length; ++$i) {
            if (self::MATCH === $segments[$i][0]) {

                if (!isset($segments[$i][0])) {
                    throw new \InvalidArgumentException("Empty attribute name");
                }

                $name = substr($segments[$i], 1);
                $attributes[$name] = $parts[$i];

            } else if (self::WILDCARD !== $segments[$i] && $parts[$i] !== $segments[$i]) {
                return false; // Shortcut
            }
        }

        return $attributes;
    }

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
    protected $size = 0;

    /**
     * Default constructor
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->segments = explode(self::SEP, $path);
        $this->size = count($this->segments) - 1;
    }

    /**
     * Get path as string
     *
     * @return string
     */
    public function getPathAsString()
    {
        return $this->path;
    }

    /**
     * Get last path segment (node name)
     *
     * @return string
     */
    public function getLastSegment()
    {
        return $this->segments[count($this->segments) - 1];
    }

    /**
     * Get path segments
     *
     * @return string[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * Internal recursion for find()
     *
     * @param array $ret
     * @param \USync\AST\NodeInterface[] $node
     * @param int $depth
     *
     * @return \USync\AST\NodeInterface[]
     */
    protected function _find(&$ret, Node $node, $depth = 0)
    {
        $current = $this->segments[$depth];

        if (self::MATCH === $current[0] || self::WILDCARD === $current || $node->getName() === $current) {
            if ($depth === $this->size) {
                $ret[$node->getPath()] = $node;
            } else {
                foreach ($node->getChildren() as $child) {
                    $this->_find($ret, $child, $depth + 1);
                }
            }
        }
    }

    /**
     * Does the given path matches the given pattern
     *
     * @param string $pattern
     * @param boolean $partial
     *
     * @return string[]
     *   Matched attributes. Warning the entry might be an empty array, if
     *   path does not match at all, strict false will be returned instead
     */
    public function matches($pattern, $partial = false)
    {
        return self::match($this->path, $pattern, $partial = false);
    }

    /**
     * Find matching nodes among node children
     *
     * @param Node $node
     *
     * @param string $ignoreRoot
     *
     * @return \USync\AST\NodeInterface[]
     */
    public function find(Node $node, $ignoreRoot = true)
    {
        $ret = [];

        if ($ignoreRoot && $node->isRoot()) {
            foreach ($node->getChildren() as $child) {
                $this->_find($ret, $child);
            }
        } else {
            $this->_find($ret, $node);
        }

        return $ret;
    }
}
