<?php

namespace USync\TreeBuilding;

use USync\AST\BooleanValueNode;
use USync\AST\Node;
use USync\AST\NullValueNode;
use USync\AST\ValueNode;
use USync\AST\Path;

class ArrayTreeBuilder
{
    // @todo Extract this into some hook ?
    static public $pathMap = [
        'field.?name'                      => '\USync\AST\Drupal\FieldNode',
        'entity.?type.?bundle'             => '\USync\AST\Drupal\EntityNode',
        'entity.?type.?bundle.field.?name' => '\USync\AST\Drupal\FieldInstanceNode',
        'security.role.?name'              => '\USync\AST\Drupal\RoleNode',
        'view.?type.?bundle.?name'         => '\USync\AST\Drupal\ViewNode',
        'variable.?name'                   => '\USync\AST\Drupal\VariableNode',
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
                    /* @var $node \USync\AST\Drupal\DrupalNodeInterface */
                    $node = new $class($name, $value);
                    $node->setAttributes($attributes);
                    break;
                }
            }
        }

        if (null === $value || 'delete' === $value) {
            $node->setAttribute('delete', true);
        }

        if (null === $node) {
            switch (gettype($value)) {

                case "array":
                case "object":
                    $node = new Node($name, $value);
                    break;

                case "boolean":
                    $node = new BooleanValueNode($name, $value);
                    break;

                case "NULL":
                    $node = new NullValueNode($name, $value);
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
     * Same as parse, but do not add a root
     *
     * @param array $array
     *
     * @return \USync\AST\Node[]
     *   Fully built child nodes
     */
    public function parseWithoutRoot(array $array)
    {
        $ast = $this->parse($array);

        return $ast->getChildren();
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
