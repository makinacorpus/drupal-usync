<?php

namespace USync;

use USync\AST\Node;
use USync\AST\InheritProcessor;
use USync\AST\Visitor;
use USync\Helper\FieldHelper;
use USync\Helper\FieldInstanceHelper;
use USync\Helper\HelperInterface;
use USync\Helper\NodeEntityHelper;

class Runner
{
    /**
     * @var \USync\Helper\HelperInterface[]
     */
    protected $helpers = array();

    /**
     * Default constructor
     *
     * @param \USync\Context $context
     *   @todo This should move out
     */
    public function __construct(Context $context)
    {
        // @todo Make this better
        // Content types first.
        // @todo Fetch a map of used fields.
        // Always process fields first.
        // @todo Do not import non used fields.
        $instanceHelper = new FieldInstanceHelper();
        $instanceHelper->setContext($context);
        $fieldHelper = new FieldHelper($instanceHelper);
        $fieldHelper->setContext($context);
        $nodeHelper = new NodeEntityHelper($fieldHelper);
        $nodeHelper->setContext($context);
        $this->helpers = array(
            'field.%' => $fieldHelper,
            'entity.node.%' => $nodeHelper,
            'entity.%.field' => $instanceHelper,
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
        $object = $node->getValue();

        // Deal with magic values first.
        if (is_string($object)) {
            switch ($object) {

                case 'delete':
                    if ($helper->exists($path)) {
                        $helper->deleteExistingObject($path);
                    }
                    return;

                // Any object marked as default will inherit from the Drupal
                // defaults or any previous definition known only by helper:
                // for example, any field instance set to default will inherit
                // from label and widget defined at the field level
                case 'default':
                    $object = array();
                    break;
            }
        }

        if ($helper->exists($path)) {

            $existing = $helper->getExistingObject($path);

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
                    $context->logError(sprintf("%s malformed 'keep' property, must be 'all' or an array of string property names", $path));
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
                    $context->logError(sprintf("%s malformed 'drop' property, must be an array of string property names", $path));
                }
            }
        }

        unset($object['keep'], $object['drop']);

        $helper->synchronize($path, $object);
    }

    /**
     * Run changes following the given configuration
     *
     * @param Config $config
     */
    public function run(Node $config, Context $context)
    {
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
