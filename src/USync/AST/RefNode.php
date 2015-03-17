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
        $this->ref = new $node;
    }

    public function getReference()
    {
        return $this->ref;
    }

    public function referenceExists()
    {
        return null === $this->ref || $this->ref instanceof StubNode;
    }
}
