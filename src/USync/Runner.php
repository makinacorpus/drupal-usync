<?php

namespace USync;

use USync\AST\Node;
use USync\Helper\FieldHelper;
use USync\Helper\FieldInstanceHelper;
use USync\Helper\HelperInterface;
use USync\Helper\NodeEntityHelper;
use USync\Parsing\Preprocessor;

class Runner extends AbstractContextAware
{
    protected $helpers = array();

    public function __construct(Context $context)
    {
        $this->setContext($context);

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

    public function processObject($path, array $object, HelperInterface $helper)
    {
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
                    $this->getContext()->logError(sprintf("%s malformed 'keep' property, must be 'all' or an array of string property names", $path));
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
                    $this->getContext()->logError(sprintf("%s malformed 'drop' property, must be an array of string property names", $path));
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
    public function run(Node $config)
    {
        $preprocessor = new Preprocessor();
        $preprocessor->setContext($this->getContext());
        $preprocessor->execute($config);

        foreach ($this->helpers as $pattern => $helper) {
            foreach ($config->find($pattern) as $path => $node) {
                $helper->synchronize($path, $node->getValue());
            }
        }
    }
}
