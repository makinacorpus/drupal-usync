<?php

namespace USync\AST;

class RefNode extends Node
{
    /**
     * @var USync\AST\NodeInterface
     */
    protected $ref;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
    }

    public function setReference(NodeInterface $node)
    {
        $this->ref = $node;
    }

    public function getReference()
    {
        if (null === $this->ref) {
            throw new \LogicException(sprintf("%s: referenced node is not set", $this->path));
        }

        return $this->ref;
    }

    public function referenceExists()
    {
        return null === $this->ref;
    }
}
