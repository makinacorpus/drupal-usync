<?php

namespace USync\AST;

class Path
{
    /**
     * Path separator
     */
    const SEP = '.';

    /**
     * Path unique segment wildcard
     */
    const WILDCARD = '%';

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

        if (self::WILDCARD === $current || $node->getName() === $current) {
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
