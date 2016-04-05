<?php

namespace USync\Serializing;

use USync\AST\Node;
use USync\AST\NodeInterface;

use Symfony\Component\Yaml\Yaml;

class YamlSerializer implements SerializerInterface
{
    public function getFileExtensions()
    {
        return array('yml', 'yaml');
    }

    public function serialize(NodeInterface $node)
    {
        if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
            if (!@include_once __DIR__ . '/../../../vendor/autoload.php') {
                throw new \LogicException("Unable to find the \Symfony\Component\Yaml\Yaml class");
            }
        }

        return Yaml::dump($node->getValue(), 32, 2);
    }
}
