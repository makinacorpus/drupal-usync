<?php

namespace USync\Loading;

use USync\AST\Drupal\EntityNode;
use USync\AST\NodeInterface;
use USync\Context;
use USync\TreeBuilding\ArrayTreeBuilder;

class VocabularyEntityLoader extends AbstractEntityLoader
{
    static private $defaults = [
        'name'        => '',
        'description' => '',
        'hierarchy'   => null,
    ];

    public function __construct()
    {
        parent::__construct('vocabulary');
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node EntityNode */
        $bundle = $node->getBundle();
        $vocabulary = taxonomy_vocabulary_machine_name_load($bundle);

        $exists = (int)db_query("SELECT 1 FROM {taxonomy_term_data} d WHERE d.vid = :vid", [':vid' => $vocabulary->vid]);

        if ($exists) {
            $context->logDataloss(sprintf("%s: taxonomy vocabulary has terms", $node->getPath()));
        }

        taxonomy_vocabulary_delete($vocabulary->vid);
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        /* @var $node EntityNode */
        if (!$this->exists($node, $context)) {
            $context->logCritical(sprintf("%s: node type does not exist", $node->getPath()));
        }

        $existing = array_intersect_key(
            (array)taxonomy_vocabulary_machine_name_load($node->getBundle()),
            self::$defaults
        );

        return array_diff(
            $existing,
            self::$defaults
        );
    }

    public function getDependencies(NodeInterface $node, Context $context)
    {
        // @todo Anything at all we could do here?
        return [];
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
        $machineName = $node->getBundle();

        $object = $node->getValue();
        if (!is_array($object)) {
            $object = [];
        }

        if (empty($object['description'])) {
            $description = '';
        } else {
            $description = $object['description'];
        }

        if ($node->isMerge() && ($existing = $this->getExistingObject($node, $context))) {
            $info = [
                'machine_name'  => $machineName,
                'description'   => $description,
            ] + $object + $existing;
        } else {
            $info = [
                'machine_name'  => $machineName,
                'description'   => $description,
            ] + $object + self::$defaults;
        }

        if (empty($info['name'])) {
            $context->logWarning(sprintf('%s: has no name', $node->getPath()));
            $info['name'] = $machineName;
        }

        taxonomy_vocabulary_save((object)$info);
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof EntityNode && 'vocabulary' === $node->getEntityType();
    }
}
