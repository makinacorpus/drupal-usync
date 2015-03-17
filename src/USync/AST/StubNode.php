<?php

namespace USync\AST;

class StubNode implements NodeInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var \USync\AST\Node
     */
    protected $parent;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * Default constructor
     *
     * @param string $name
     *   Node local name in the graph
     * @param \USync\AST\Node $parent
     *   Parent node in the graph
     */
    public function __construct($name, $value = null)
    {
        $this->name = $name;
    }

    public function isTerminal()
    {
        return true;
    }

    public function isExternal()
    {
        return true;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setProperty($name, $value)
    {
    }

    public function getProperty($name)
    {
        throw new \InvalidArgumentException(sprintf("%s property does not exists", $name));
    }

    public function getProperties()
    {
        return [];
    }

    public function setBaseNode(NodeInterface $node)
    {
    }

    public function getBaseNode()
    {
    }

    public function hasChild($key)
    {
        return isset($this->children[$key]);
    }

    public function addChild(NodeInterface $node)
    {
        throw new \LogicException("I am terminal");
    }

    public function removeChild($key)
    {
        throw new \InvalidArgumentException(sprintf("%s child does not exist", $key));
    }

    public function getChild($key)
    {
        throw new \InvalidArgumentException(sprintf("%s child does not exist", $key));
    }

    public function setParent(NodeInterface $parent)
    {
        $this->parent = $parent;

        if (null === $parent) {
            $this->path = '';
        } else if (empty($parent->path)) {
            $this->path = $this->name;
        } else {
            $this->path = $parent->path . Path::SEP . $this->name;
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasParent()
    {
        return !empty($this->parent);
    }

    public function isRoot()
    {
        return !$this->hasParent();
    }

    public function getRoot()
    {
        if (null === $this->parent) {
            return $this;
        }

        $node = $this->parent;
        while ($node->parent) {
            $node = $node->parent;
        }

        return $node;
    }

    public function getChildren()
    {
        return [];
    }

    public function getValue()
    {
        return null;
    }

    public function find($path)
    {
        return [];
    }
}
