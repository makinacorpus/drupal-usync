<?php

namespace USync\Loading;


use USync\AST\Drupal\InputFilterNode;
use USync\AST\NodeInterface;
use USync\Context;

class InputFilterLoader extends AbstractLoader
{
    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'filter_format';
    }

    /**
     * Return the filter name depending on the tree structure
     *
     * @param NodeInterface $node
     * @param Context $context
     * @return array|mixed|string
     */
    protected function getFormatName(NodeInterface $node, Context $context)
    {
        if ($node->hasChild('name')) {
            $value = $node->getChild('name')->getValue();

            if (!is_string($value)) {
                $context->logCritical(sprintf("%s: name attribute is not a string", $node->getPath()));
            }

            return $value;
        }

        return $node->getName();
    }


    /**
     * {@inheritDoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        return filter_format_exists($node->getName()) !== false;
    }

    /**
     * Returns a fully loaded input filter structure.
     *
     * @param NodeInterface $node
     * @param Context $context
     * @return bool|\stdClass
     */
    protected function loadExistingInputFilter(NodeInterface $node, Context $context)
    {
        $existing = filter_format_load($node->getName());

        $filters = db_query("SELECT name, settings FROM {filter} WHERE format = ? AND status = 1 ORDER BY weight ASC", [$node->getName()])->fetchAllKeyed();

        foreach ($filters as $name => $settings) {
            if (is_string($settings)) {
                $settings = unserialize($settings);
            }
            if (!$existing->filters) {
                $existing->filters = [];
            }
            if (empty($settings)) {
                $settings = true;
            }
            $existing->filters[$name] = $settings;
        }

        return $existing;
    }

    /**
     * {@inheritDoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        return $this->loadExistingInputFilter($node, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof InputFilterNode;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        return filter_format_disable($node->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $object = [
          'name'   => $this->getFormatName($node, $context),
          'format' => $node->getName(),
        ];

        // Handle permissions as well.
        $filters = [];

        $weight = 0;
        if ($node->hasChild('filters')) {

            $valid = array_keys(module_invoke_all('filter_info'));

            foreach ($node->getChild('filters')->getChildren() as $filter) {
                $name = $filter->getName();

                if (!in_array($name, $valid)) {
                    $context->logWarning(sprintf("%s: filter does not exists, ignoring", $filter->getPath()));
                    continue;
                }

                $filterValue = $filter->getValue();
                if (true === $filterValue || empty($filterValue)) {
                    $filterValue = [];
                }
                if (!isset($filterValue['weight'])) {
                    $filterValue['weight'] = $weight;
                }

                $filters[$name] = $filterValue;
                $filters[$name]['status'] = 1;

                ++$weight;
            }
        }
        $object['filters'] = $filters;

        $format = (object)$object;
        filter_format_save($format);
    }
}
