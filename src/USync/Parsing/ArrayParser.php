<?php

namespace USync\Parsing;

use USync\AST\BooleanNode;
use USync\AST\Node;
use USync\AST\NullNode;
use USync\AST\ValueNode;
use USync\AST\Path;

class ArrayParser
{
    static $pathMap = [
        'field.%'             => '\USync\AST\Drupal\FieldNode',
        'entity.%.%'          => '\USync\AST\Drupal\EntityNode',
        'entity.%.%.field.%'  => '\USync\AST\Drupal\FieldInstanceNode',
        'view.%'              => '\USync\AST\Drupal\EntityRefNode',
        'view.%.%'            => '\USync\AST\Drupal\ViewCollectionNode',
        'view.%.%.%'          => '\USync\AST\Drupal\ViewNode',
        'variable.%'          => '\USync\AST\Drupal\VariableNode',
    ];

    protected function _parse(Node $parent, $name, $value = null)
    {
        if (empty($parent->getPath())) {
            $path = $name;
        } else {
            $path = $parent->getPath() . Path::SEP . $name;
        }

        $node = null;

        if (null === $node) {
            foreach (self::$pathMap as $pattern => $class) {
                if (class_exists($class) && Path::match($path, $pattern)) {
                    $node = new $class($name, $value);
                    break;
                }
            }
        }

        if (null === $node) {
            switch (gettype($value)) {

                case "array":
                case "object":
                    $node = new Node($name, $value);
                    break;

                case "boolean":
                    $node = new BooleanNode($name, $value);
                    break;

                case "NULL":
                    $node = new NullNode($name, $value);
                    break;

                case "integer":
                case "double":
                case "string":
                case "resource":
                default:
                    $node = new ValueNode($name, $value);
                    break;
            }
        }

        $parent->addChild($node);

        if (!$node->isTerminal() && is_array($value) || $value instanceof \Traversable) {
            foreach ($value as $key => $sValue) {
                $this->_parse($node, $key, $sValue);
            }
        }
    }

    /**
     * Parse data from structured array
     *
     * @param array $array
     *
     * @return \USync\AST\Node
     *   Fully built AST
     */
    public function parse(array $array)
    {
        $ast = new Node('root');

        foreach ($array as $key => $value) {
            $this->_parse($ast, $key, $value);
        }

        return $ast;
    }
}
