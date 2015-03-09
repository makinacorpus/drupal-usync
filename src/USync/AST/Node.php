<?php

namespace USync\AST;

class Node
{
    static public function createNode($array, $name = 'root')
    {
        if (!is_array($array)) {
            return new ValueNode($array);
        }

        $node = new Node($name);

        foreach ($array as $key => $value) {
            $node->addChild($key, self::createNode($value, $key));
        }

        return $node;
    }

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
    protected $path;

    /**
     * The node this one inherits from
     *
     * @var \USync\AST\Node
     */
    protected $baseNode;

    /**
     * @var \USync\AST\Node[]
     */
    protected $children = array();

    /**
     * Default constructor
     *
     * @param string $name
     *   Node local name in the graph
     * @param \USync\AST\Node $parent
     *   Parent node in the graph
     */
    public function __construct($name, Node $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        if (null === $parent) {
            $this->path = $name;
        } else {
            $this->path = $parent->path . Path::SEP . $name;
        }
    }

    /**
     * Is this node terminal
     *
     * @return boolean
     */
    public function isTerminal()
    {
        return false;
    }

    /**
     * Get node local name in the graph
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get node path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the node which this node inherits from
     * 
     * @param \USync\AST\Node $node
     */
    public function setBaseNode(Node $node)
    {
        $this->baseNode = $node;
    }

    /**
     * Get the node this node inherits from
     *
     * @return \USync\AST\Node
     */
    public function getBaseNode()
    {
        return $this->baseNode;
    }

    /**
     * Does the identifier children exist
     *
     * @param string $key
     */
    public function hasChild($key)
    {
        return isset($this->children[$key]);
    }

    /**
     * Add child
     *
     * @param string $key
     *   Local child name in the graph
     * @param \USync\AST\Node $node
     */
    public function addChild($key, Node $node)
    {
        if ($this->isTerminal()) {
            throw new \LogicException("I am terminal");
        }

        $this->children[$key] = $node;
    }

    public function getChild($key)
    {
        if (!isset($this->children[$key])) {
            throw new \InvalidArgumentException(sprintf("%s child does not exist", $key));
        }

        return $this->children[$key];
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

            // Ensure inheritance is propagated in result array
            if ($this->baseNode) {
                if ($base = $this->baseNode->getValue()) {
                    $ret = drupal_array_merge_deep($base, $ret);
                }
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
}
