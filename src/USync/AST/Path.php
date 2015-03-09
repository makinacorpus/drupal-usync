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
     * Default constructor
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->segments = explode(self::SEP, $path);
    }

    protected function _find(Node $node, array $segments)
    {
        $ret = array();

        $current = array_shift($segments);

        if ('%' === $current || $node->getName() === $current) {
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
