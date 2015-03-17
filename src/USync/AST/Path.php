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
     * Property name wildcard
     */
    const MATCH = '?';

    /**
     * Does the given path matches the given pattern
     *
     * @param string $path
     * @param string $pattern
     * @param boolean $partial
     *
     * @return boolean
     */
    static public function match($path, $pattern, $partial = false)
    {
        $properties = array();

        $parts = explode(self::SEP, $path);
        $segments = explode(self::SEP, $pattern);

        $length = count($parts);

        if ($length !== count($segments)) {
            return false;
        }

        for ($i = 0; $i < $length; ++$i) {
            if (self::MATCH === $segments[$i][0]) {
                if (!isset($segments[$i][0])) {
                    throw new \InvalidArgumentException("Empty property name");
                }
                $name = substr($segments[$i], 1);
                $properties[$name] = $parts[$i];
            } else if (self::WILDCARD !== $segments[$i] && $parts[$i] !== $segments[$i]) {
                return false; // Shortcut
            }
        }

        return $properties;
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
