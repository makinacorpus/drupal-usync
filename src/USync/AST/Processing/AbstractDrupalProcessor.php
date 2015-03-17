<?php

namespace USync\AST\Processing;

use USync\AST\Node;
use USync\AST\BooleanNode;
use USync\AST\DefaultNode;
use USync\AST\ValueNode;
use USync\Context;
use USync\Helper\HelperInterface;

abstract class AbstractDrupalProcessor implements ProcessorInterface, HelperInterface
{
    /**
     * Implementation for execute()
     *
     * @param Node $node
     * @param Context $context
     */
    public function _execute(Node $node, Context $context)
    {
        $path = $node->getPath();

        if ($node instanceof DeleteNode || $node instanceof NullNode) {
            $mode = 'delete';
        } else if ($node instanceof DefaultNode) {
            $mode = 'sync';
        } else if ($node instanceof BooleanNode) {
            if ($node->getValue()) {
                $mode = 'sync';
            } else {
                $mode = 'delete';
            }
        } else if ($node instanceof ValueNode) {
            $context->logError(sprintf("%s: invalid value type, ignoring", $path));
            return;
        } else {
            $mode = 'sync';
        }

        switch ($mode) {

            case 'delete':
                $context->log(sprintf(" - %s", $path));
                if ($this->exists($path, $context)) {
                    $this->deleteExistingObject($path, $context);
                }
                break;

            case 'sync':
                $object = $node->getValue();

                if (!is_array($object)) {
                    $object = array();
                }

                if ($this->exists($path, $context)) {
                    $context->log(sprintf(" ~ %s", $path));

                    $existing = $this->getExistingObject($path, $context);

                    // Proceed to merge accordingly to 'keep' and 'drop' keys.
                    if (!empty($object['keep'])) {
                        if ('all' === $object['keep']) {
                            drupal_array_merge_deep($existing, $object);
                        } else if (is_array($object['keep'])) {
                            foreach ($object['keep'] as $key) {
                                if (array_key_exists($key, $existing)) {
                                    $object[$key] = $existing[$key];
                                }
                            }
                        } else {
                            $context->logError(sprintf("%s: malformed 'keep' property, must be 'all' or an array of string property names", $path));
                        }
                    }
                    if (!empty($object['drop'])) {
                        if (is_array($object['drop'])) {
                            foreach ($object['drop'] as $key) {
                                if (isset($object[$key])) {
                                    unset($object[$key]);
                                }
                            }
                        } else {
                            $context->logError(sprintf("%s: malformed 'drop' property, must be an array of string property names", $path));
                        }
                    }
                } else {
                    $context->log(sprintf(" + %s", $path));
                }

                unset($object['keep'], $object['drop']);

                $this->synchronize($node, $context);
                break;
        }
    }

    /**
     * Does the given node matches this processor
     *
     * @param Node $node
     */
    abstract public function matches(Node $node);

    public function execute(Node $node, Context $context)
    {
        if ($this->matches($node)) {
            return $this->_execute($node, $context);
        }
    }
}
