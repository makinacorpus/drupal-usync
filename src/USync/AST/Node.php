<?php

namespace USync\AST;

class Node implements NodeInterface
{
    /**
     * Internal recursion for createNode()
     *
     * @param mixed $value
     * @param string $name
     * @param Node $parent
     *
     * @return \USync\AST\Node
     */
    static protected function _createNode($value, $name = 'root', Node $parent = null)
    {
        if (!is_array($value)) {
            if (null === $value) {
                $node = new NullNode($name, $parent);
            } else if (is_bool($value)) {
                $node = new BooleanNode($name, $parent, $value);
            } else if ('delete' === $value) {
                $node = new DeleteNode($name, $parent);
            } else if ('default' === $value) {
                $node = new DefaultNode($name, $parent);
            } else {
                $node = new ValueNode($name, $parent, $value);
            }
        } else {
            $node = new Node($name, $parent);
            foreach ($value as $key => $value) {
                $node->addChild($key, self::_createNode($value, $key, $node));
            }
        }
        return $node;
    }

    /**
     * Recursively build the AST from the given array
     *
     * @param mixed $value
     *
     * @return \USync\AST\Node
     */
    static public function createNode($value)
    {
        return self::_createNode($value);
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
            $this->path = '';
        } else if (empty($parent->path)) {
            $this->path =   $name;
        } else {
            $this->path = $parent->path . Path::SEP . $name;
        }
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

    public function addChild($key, NodeInterface $node)
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
