<?php

namespace USync\AST;

class Node implements NodeInterface
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
     * The node this one inherits from
     *
     * @var \USync\AST\Node
     */
    protected $baseNode;

    /**
     * @var \USync\AST\Node[]
     */
    protected $children = [];

    /**
     * @var mixed[]
     */
    protected $attributes = [];

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
        return false;
    }

    public function isExternal()
    {
        return false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        throw new \InvalidArgumentException(sprintf("'%s' attribute does not exists", $name));
    }

    public function setAttributes($attributes)
    {
        return $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setBaseNode(NodeInterface $node)
    {
        $this->baseNode = $node;
    }

    public function getBaseNode()
    {
        return $this->baseNode;
    }

    public function hasChild($key)
    {
        return isset($this->children[$key]);
    }

    public function addChild(NodeInterface $node)
    {
        if ($this->isTerminal()) {
            throw new \LogicException("I am terminal");
        }

        $node->setParent($this);

        $this->children[$node->getName()] = $node;
    }

    public function removeChild($key)
    {
        if (!isset($this->children[$key])) {
            throw new \InvalidArgumentException(sprintf("%s child does not exist", $key));
        }

        unset($this->children[$key]);
    }

    public function getChild($key)
    {
        if (!isset($this->children[$key])) {
            throw new \InvalidArgumentException(sprintf("%s child does not exist", $key));
        }

        return $this->children[$key];
    }

    public function getChildAt($index)
    {
        if (count($this->children) < $index) {
            throw new \OutOfBoundsException(sprintf("%sth child is out of bounds", $index));
        }

        $count = 0;
        foreach ($this->children as $child) {
            if ($count === $index) {
                return $child;
            }
            ++$count;
        }
    }

    public function getChildPosition(NodeInterface $node)
    {
        $count = 0;
        foreach ($this->children as $child) {
            if ($node === $child) {
                return $count;
            }
            ++$count;
        }

        throw new \InvalidArgumentException(sprintf("Not a child"));
    }

    public function getPosition()
    {
        if (!$this->parent) {
            return 0;
        }

        return $this->parent->getChildPosition($this);
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

        // Re-parent children accordingly (update their own path)
        foreach ($this->children as $child) {
            $child->setParent($this);
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
        return $this->children;
    }

    public function getValue()
    {
        $ret = array();

        foreach ($this->children as $key => $node)  {
            $ret[$key] = $node->getValue();
        }

        // Ensure inheritance is propagated in result array
        if ($this->baseNode) {
            if ($base = $this->baseNode->getValue()) {
                $ret = drupal_array_merge_deep($base, $ret);
            }
        }

        return $ret;
    }

    public function find($path)
    {
        if (is_string($path)) {
            $path = new Path($path);
        }

        return $path->find($this);
    }

    public function mergeWith(NodeInterface $node, $deep = true)
    {
        foreach ($node->getChildren() as $key => $child) {
            if (!$this->hasChild($key)) {
                // @wrong, deep clone won't work and we will keep odd references
                // to the $node children in the other tree...
                $this->addChild(clone $child);
            } else if ($deep) {
                $this->getChild($key)->mergeWith($child, $deep);
            }
        }
    }
}
