<?php

namespace USync\Serializing;

use USync\AST\NodeInterface;

interface SerializerInterface
{
    /**
     * Return a list of supported file extensions
     *
     * @return string[]
     */
    public function getFileExtensions();

    /**
     * From given blob read data and return a nice formatted array for
     * usage with the Node class
     *
     * @param USync\AST\NodeInterface $node
     *
     * @return string
     *   String data
     */
    public function serialize(NodeInterface $node);
}
