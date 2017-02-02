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

    public function exists(NodeInterface $node, Context $context)
    {
        /* @var $node EntityNode */

        // Sorry but vocabulary machine name does not reflect a bundle
        if (taxonomy_vocabulary_machine_name_load($node->getBundle())) {
            return true;
        }

        return false;
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
            self::$defaults + ['vid' => null]
        );

        return array_diff(
            $existing,
            self::$defaults
        );
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

            if ($vocabulary = taxonomy_vocabulary_machine_name_load($machineName)) {
                // So an existing object is here, but the vid parameter is
                // actually never loaded (we don't want to export it when we
                // are building a yml file) - therefore we need to load it
                // once again. In case we didn't set the vid, the taxonomy
                // save method will attempt an SQL INSERT and cause bad SQL
                // STATE errors (constraint violation)
                $info['vid'] = $vocabulary->vid;
            }
        }

        if (empty($info['name'])) {
            $context->logWarning(sprintf('%s: has no name', $node->getPath()));
            $info['name'] = $machineName;
        }

        taxonomy_vocabulary_save((object)$info);
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof EntityNode && 'taxonomy_term' === $node->getEntityType();
    }
}
