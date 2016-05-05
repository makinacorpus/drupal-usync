<?php

namespace USync\TreeBuilding;

use USync\AST\BooleanValueNode;
use USync\AST\ExpressionNode;
use USync\AST\MacroReferenceNode;
use USync\AST\Node;
use USync\AST\NullValueNode;
use USync\AST\StringNode;
use USync\AST\ValueNode;

/**
 * This object will parse the raw PHP array and build the initial version
 * of an AST including only primitive and code transformations directives,
 * which means that at this point, there will be no business meaning in
 * any of the nodes.
 */
class ArrayTreeBuilder
{
    /**
     * parse() method internal recursion
     *
     * @param Node $parent
     * @param string $name
     * @param mixed $value
     */
    protected function _parse(Node $parent, $name, $value = null)
    {
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

            case "string":
                if ('@=' === substr($value, 0, 2)) {
                    $node = new ExpressionNode($name, $value);
                } else if ('@' === substr($value, 0, 1)) {
                    $node = new MacroReferenceNode($name, $value);
                } else {
                    $node = new StringNode($name, $value);
                }
                break;

            case "integer":
            case "double":
            case "resource":
            default:
                $node = new ValueNode($name, $value);
                break;
        }

        if (null === $value || 'delete' === $value) {
            $node->setAttribute('delete', true);
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
    public function parseWithoutRoot(array $array, array &$symbolTable = [])
    {
        $ast = $this->parse($array, $symbolTable);

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
    public function parse(array $array, array &$symbolTable = [])
    {
        $ast = new Node('root');

        foreach ($array as $key => $value) {
            $this->_parse($ast, $key, $value, $symbolTable);
        }

        return $ast;
    }
}
