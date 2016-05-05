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
     * Does the given attribute exists
     *
     * @param string $name
     */
    public function hasAttribute($name);

    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute($name, $value);

    /**
     * Get attribute
     *
     * @param string $name
     */
    public function getAttribute($name);

    /**
     * Set all attributes, removing the existing
     *
     * @param mixed[] $attributes
     */
    public function setAttributes($attributes);

    /**
     * Get all attributes
     *
     * @return mixed[]
     */
    public function getAttributes();

    /**
     * Does the identifier children exist
     *
     * @param string $key
     */
    public function hasChild($key);

    /**
     * Add child
     *
     * @param \USync\AST\Node $node
     */
    public function addChild(NodeInterface $node);

    /**
     * Replace child with another
     *
     * @param string $key
     * @param NodeInterface $node
     */
    public function replaceChild($key, NodeInterface $node);

    /**
     * Remove child
     *
     * @param string $key
     */
    public function removeChild($key);

    /**
     * Get child
     *
     * @param string $key
     *
     * @return \USync\AST\Node
     */
    public function getChild($key);

    /**
     * Get the nth child
     *
     * @param int $index
     */
    public function getChildAt($index);

    /**
     * Get child ordered position
     *
     * @param NodeInterface $node
     *
     * @return int
     */
    public function getChildPosition(NodeInterface $node);

    /**
     * Get current node position relative to parent
     *
     * @return int
     */
    public function getPosition();

    /**
     * Set parent and update internals accordingly
     *
     * @param NodeInterface $parent
     */
    public function setParent(NodeInterface $parent);

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
     * Please note that per spec children are ordered
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

    /**
     * Merge this node content using the other node content
     *
     * @param NodeInterface $node
     * @param boolean $deep
     *   If set to false, the current node will keep its childs when conflicting
     */
    public function mergeWith(NodeInterface $node, $deep = true);

    /**
     * Clone the current node
     *
     * @param string $newName
     *   Set this to change the duplicate name
     *
     * @return NodeInterface
     */
    public function duplicate($newName = null);
}
