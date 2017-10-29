<?php

namespace USync\Loading;

use USync\AST\Drupal\EntityNode;
use USync\AST\NodeInterface;
use USync\Context;
use USync\TreeBuilding\ArrayTreeBuilder;

class NodeEntityLoader extends AbstractEntityLoader implements VerboseLoaderInterface
{
    static private $defaults = [
        'name'        => '',
        'base'        => 'node_content',
        'modified'    => 0,
        'has_title'   => true,
        'title_label' => "Title", // Fallback to default language (en)
        'locked'      => false,
    ];

    static private $settings = [
        'submitted' => 0,
        'preview'   => 0,
        'options'   => ['status', 'promote']
    ];

    public function __construct()
    {
        parent::__construct('node');
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node EntityNode */
        $bundle = $node->getName();
        $exists = (int)db_query("SELECT 1 FROM {node} WHERE type = :type", array(':type' => $bundle))->fetchField();

        if ($exists) {
            $context->logDataloss(sprintf("%s: node type has nodes", $node->getPath()));
        }

        node_type_delete($bundle);
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        /* @var $node EntityNode */
        if (!$this->exists($node, $context)) {
            $context->logCritical(sprintf("%s: node type does not exist", $node->getPath()));
        }

        $existing = array_intersect_key(
            (array)node_type_load($node->getName()),
            self::$defaults
        );

        $existing = array_diff($existing, self::$defaults);

        $settings = [];
        foreach (self::$settings as $key => $defaultValue) {
            $variable = 'node_' . $key . '_' . $node->getBundle();

            $value = variable_get($variable);

            if ($value != $defaultValue) {
                $settings[$key] = $value;
            }
        }

        if (!empty($settings)) {
            $existing['settings'] = $settings;
        }

        return $existing;
    }

    public function getDependencies(NodeInterface $node, Context $context)
    {
        /* @var $node EntityNode */
        $order = [];

        $bundle = $node->getBundle();

        // First, fields
        $field = [];
        foreach (field_info_instances('node', $bundle) as $instance) {
            $field[] = 'entity.node.' . $bundle . '.field.' . $instance['field_name'];
            $order[] = isset($instance['weight']) ? $instance['weight'] : 0;
        }

        array_multisort($order, $field);

        return $field;
    }

    public function getExtractDependencies(NodeInterface $node, Context $context)
    {
        $ret = $this->getDependencies($node, $context);

        $bundle = $node->getBundle();

        // Let's go for view modes too
        $view = [];
        $view[] = 'view.node.' . $bundle . '.default';
        foreach (entity_get_info('node')['view modes'] as $viewMode => $settings) {
            $view[] = 'view.node.' . $bundle . '.' . $viewMode;
        }

        return array_merge($ret, $view);
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        /* @var $node EntityNode */
        $object = $this->getExistingObject($node, $context);

        $builder = new ArrayTreeBuilder();

        foreach ($builder->parseWithoutRoot($object) as $child) {
            $node->addChild($child);
        }
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node EntityNode */
        $bundle = $node->getName();

        $object = $node->getValue();
        if (!is_array($object)) {
            $object = array();
        }

        if ($node->isMerge() && ($existing = $this->getExistingObject($node, $context))) {
            $info = array(
                'type'      => $bundle,
                'custom'    => false,
            ) + $object + $existing;
        } else {
            $info = array(
                'type'      => $bundle,
                'custom'    => false,
            ) + $object + self::$defaults + array(
                'module'    => 'node',
                'orig_type' => null,
            );
        }

        if (empty($info['name'])) {
            $context->logWarning(sprintf('%s: has no name', $node->getPath()));
            $info['name'] = $bundle;
        }

        node_type_save((object)$info);

        if ($node->hasChild('settings')) {
            $settings = $node->getChild('settings')->getValue();
            if (!is_array($settings)) {
                $context->logWarning(sprintf('%s: invalid settings structure, should be an array', $node->getPath()));
            }
        }

        foreach (self::$settings as $key => $defaultValue) {
            $variable = 'node_' . $key . '_' . $node->getBundle();
            if (isset($settings[$key])) {
                variable_set($variable, $settings[$key]);
            } else {
                variable_set($variable, $defaultValue);
            }
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof EntityNode && 'node' === $node->getEntityType();
    }

    /**
     * {inheritdoc}
     */
    public function getLoaderName()
    {
        return t("Content type");
    }

    /**
     * {inheritdoc}
     */
    public function getLoaderDescription()
    {
        return t("Loads this node as content type.");
    }

    /**
     * {inheritdoc}
     */
    public function getNodeName(NodeInterface $node)
    {
        $object = $node->getValue();

        if (!empty($object['name'])) {
            return (string)$object['name'];
        }
    }

    /**
     * {inheritdoc}
     */
    public function getNodeInformation(NodeInterface $node)
    {
        return null; // Sorry, not implemented
    }
}
