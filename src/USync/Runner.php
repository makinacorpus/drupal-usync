<?php

namespace USync;

use USync\AST\BooleanNode;
use USync\AST\DefaultNode;
use USync\AST\DeleteNode;
use USync\AST\InheritProcessor;
use USync\AST\Node;
use USync\AST\NullNode;
use USync\AST\ValueNode;
use USync\AST\Visitor;
use USync\Helper\FieldHelper;
use USync\Helper\FieldInstanceHelper;
use USync\Helper\HelperInterface;
use USync\Helper\NodeEntityHelper;
use USync\Helper\ViewModeHelper;

class Runner
{
    /**
     * @var \USync\Helper\HelperInterface[]
     */
    protected $helpers = array();

    /**
     * Default constructor
     */
    public function __construct()
    {
        // @todo Make this better
        // Content types first.
        // @todo Fetch a map of used fields.
        // Always process fields first.
        // @todo Do not import non used fields.
        $instanceHelper = new FieldInstanceHelper();
        $fieldHelper = new FieldHelper($instanceHelper);
        $nodeHelper = new NodeEntityHelper($fieldHelper);
        $viewHelper = new ViewModeHelper();
        $this->helpers = array(
            'field.%' => $fieldHelper,
            'entity.node.%' => $nodeHelper,
            'entity.%.%.field.%' => $instanceHelper,
            // 'view.%.%.%' => $viewHelper, // @todo Unstable yet
        );
    }

    /**
     * Process the given node using the given helper
     *
     * @param Node $node
     * @param HelperInterface $helper
     */
    public function processObject(Node $node, HelperInterface $helper, Context $context)
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
                if ($helper->exists($path, $context)) {
                    $helper->deleteExistingObject($path, $context);
                }
                return;

            case 'sync':
                $object = $node->getValue();

                if (!is_array($object)) {
                    $object = array();
                }

                if ($helper->exists($path, $context)) {

                    $existing = $helper->getExistingObject($path, $context);

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
                }

                unset($object['keep'], $object['drop']);

                $helper->synchronize($path, $object, $context);
                break;
        }
    }

    /**
     * Run changes following the given configuration
     *
     * @param Config $config
     */
    public function run(Context $context)
    {
        $config = $context->getGraph();

        $visitor = new Visitor();
        $visitor->addProcessor(new InheritProcessor());
        $visitor->execute($config, $context);

        // @todo This should be a visitor to, but based upon pattern matching
        foreach ($this->helpers as $pattern => $helper) {
            foreach ($config->find($pattern) as $node) {
                $this->processObject($node, $helper, $context);
            }
        }
    }
}
