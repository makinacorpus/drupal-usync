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
        'field.%'                      => '\USync\AST\Drupal\FieldNode',
        'entity.?type.?bundle'         => '\USync\AST\Drupal\EntityNode',
        'entity.?type.?bundle.field.%' => '\USync\AST\Drupal\FieldInstanceNode',
        'view.?type.?bundle.%'         => '\USync\AST\Drupal\ViewNode',
        'variable.%'                   => '\USync\AST\Drupal\VariableNode',
    ];

    protected function _parse(Node $parent, $name, $value = null)
    {
        if (empty($parent->getPath())) {
            $path = $name;
        } else {
            $path = $parent->getPath() . Path::SEP . $name;
        }

        $node = null;

        foreach (self::$pathMap as $pattern => $class) {
            if (class_exists($class)) {
                $attributes = Path::match($path, $pattern);
                if ($attributes !== false) {
                    $node = new $class($name, $value);
                    $node->setAttributes($attributes);
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
