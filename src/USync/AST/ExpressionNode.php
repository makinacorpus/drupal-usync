<?php

namespace USync\AST;

/**
 * Node that contains something that should be evaluated.
 */
class ExpressionNode extends StringNode
{
    /**
     * @var boolean
     */
    protected $evaluated = false;

    /**
     * Get expression
     *
     * @return string
     */
    public function getExpression()
    {
        return substr($this->getValue(), 2);
    }

    /**
     * Evaluate expression and replace value internally
     */
    public function evaluateExpression()
    {
        if ($this->evaluated) {
            return;
        }
        $this->evaluated = true;

        $value = eval('return ' . $this->getExpression() . ';');

        if (false === $value) {
            throw new \RuntimeException("Evaluation failed");
        }

        $this->value = $value;
    }
}
