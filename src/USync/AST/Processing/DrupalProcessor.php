<?php

namespace USync\AST\Processing;

use USync\AST\Node;
use USync\AST\BooleanValueNode;
use USync\AST\DefaultNode;
use USync\AST\DeleteNode;
use USync\AST\NullValueNode;
use USync\Context;
use USync\Loading\LoaderInterface;
use USync\AST\Drupal\DrupalNodeInterface;

class DrupalProcessor implements ProcessorInterface
{
    /**
     * @var \USync\Loading\LoaderInterface[]
     */
    protected $loaders;

    /**
     * Default constructor
     *
     * @param \USync\Loading\LoaderInterface[] $loaders
     */
    public function __construct($loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * Implementation for execute()
     *
     * @param Node $node
     * @param Context $context
     * @param \USync\Loading\LoaderInterface $loader
     */
    public function _execute(Node $node, Context $context, LoaderInterface $loader)
    {
        if ($node instanceof DeleteNode || $node instanceof NullValueNode) {
            $mode = 'delete';
        } else if ($node instanceof DefaultNode) {
            $mode = 'sync';
        } else if ($node instanceof BooleanValueNode) {
            if ($node->getValue()) {
                $mode = 'sync';
            } else {
                $mode = 'delete';
            }
        } else {
            if ($node instanceof DrupalNodeInterface && $node->shouldDelete()) {
                $mode = 'delete';
            } else {
                $mode = 'sync';
            }
        }

        $dirtyAllowed = $node->hasAttribute('dirty') && $node->getAttribute('dirty');
        $dirtyPrefix = $dirtyAllowed ? '! ' : '';

        switch ($mode) {

            case 'delete':
                $context->log(sprintf(" - %s%s", $dirtyPrefix, $node->getPath()));
                if ($loader->exists($node, $context)) {
                    $loader->deleteExistingObject($node, $context, $dirtyAllowed);
                }
                return;

            case 'sync':
                /*
                $object = $node->getValue();

                if (!is_array($object)) {
                    $object = array();
                }
                 */

                if ($loader->exists($node, $context)) {
                    $context->log(sprintf(" ~ %s%s", $dirtyPrefix, $node->getPath()));

                    /*
                    $existing = $loader->getExistingObject($node, $context);

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
                            $context->logError(sprintf("%s: malformed 'keep' attribute, must be 'all' or an array of string attribute names", $node->getPath()));
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
                            $context->logError(sprintf("%s: malformed 'drop' attribute, must be an array of string attribute names", $node->getPath()));
                        }
                    }
                     */
                } else {
                    $context->log(sprintf(" + %s%s", $dirtyPrefix, $node->getPath()));
                }

                // unset($object['keep'], $object['drop']);

                $loader->synchronize($node, $context, $dirtyAllowed);
                break;
        }
    }

    public function execute(Node $node, Context $context)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->canProcess($node)) {
                return $this->_execute($node, $context, $loader);
            }
        }
    }
}
