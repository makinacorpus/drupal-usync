<?php

namespace USync\AST;

interface NodeInterface
{
    /**
     * Is this node terminal
     *
     * @return boolean
     */
    public function isTerminal();

    /**
     * Does this node exists in the configuration that built this tree
     *
     * @return boolean
     */
    public function isExternal();

    /**
     * Get node local name in the graph
     *
     * @return string
     */
    public function getName();

    /**
     * Get node path
     *
     * @return string
     */
    public function getPath();

    /**
     * Sets the node which this node inherits from
     * 
     * @param \USync\AST\Node $node
     */
    public function setBaseNode(NodeInterface $node);

    /**
     * Get the node this node inherits from
     *
     * @return \USync\AST\Node
     */
    public function getBaseNode();

    /**
     * Does the identifier children exist
     *
     * @param string $key
     */
    public function hasChild($key);

    /**
     * Add child
     *
     * @param string $key
     *   Local child name in the graph
     * @param \USync\AST\Node $node
     */
    public function addChild($key, NodeInterface $node);

    /**
     * Get child
     *
     * @param string $key
     *
     * @return \USync\AST\Node
     */
    public function getChild($key);

    /**
     * Get parent node if any
     *
     * @return \USync\AST\Node
     */
    public function getParent();

    /**
     * Does this node have a parent
     *
     * @return boolean
     */
    public function hasParent();

    /**
     * Is this node root
     *
     * This method is functionnally equivalent to hasParent()
     *
     * @return boolean
     */
    public function isRoot();

    /**
     * Get tree root node
     *
     * @return \USync\AST\Node
     */
    public function getRoot();

    /**
     * Get all children
     *
     * @return \USync\AST\Node
     */
    public function getChildren();

    /**
     * Get value as a multidimentional array of PHP scalar values
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Find matching node among children
     *
     * @deprecated
     *   Use \USync\AST\Path::find() directly
     *
     * @param string|Path $path
     *
     * @return \USync\AST\Node[]
     */
    public function find($path);
}
