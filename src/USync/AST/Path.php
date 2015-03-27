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
        $attributes = array();

        $parts = explode(self::SEP, $path);
        $segments = explode(self::SEP, $pattern);

        $length = count($parts);

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
     * Default constructor
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->segments = explode(self::SEP, $path);
    }

    /**
     * Get path as string
     *
     * @return \USync\AST\string
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
     * @param Node $node
     *
     * @param string[] $segments
     *   Path as a set of ordered splitted segments
     *
     * @return \USync\AST\Node[]
     */
    protected function _find(Node $node, array $segments)
    {
        $ret = array();

        $current = array_shift($segments);

        if (self::MATCH === $current[0] || self::WILDCARD === $current || $node->getName() === $current) {
            if (empty($segments)) {
                $ret[$node->getPath()] = $node;
            } else {
                foreach ($node->getChildren() as $child) {
                    $ret += $this->_find($child, $segments);
                }
            }
        }

        return $ret;
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
     * @return \USync\AST\Node[]
     */
    public function find(Node $node, $ignoreRoot = true)
    {
        $ret = array();

        if ($ignoreRoot && $node->isRoot()) {
            foreach ($node->getChildren() as $child) {
                $ret += $this->_find($child, $this->segments);
            }
        } else {
            $ret = $this->_find($node, $this->segments);
        }

        return $ret;
    }
}
