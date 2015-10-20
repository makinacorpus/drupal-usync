<?php

namespace USync\Loading;

use USync\AST\NodeInterface;
use USync\Context;
use USync\AST\Drupal\MenuNode;

class MenuLoader extends AbstractLoader
{
    static private $defaults = [
        'menu_name'   => '',
        'title'       => 'node_content',
        'description' => 0,
    ];

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'menu';
    }

    /**
     * {@inheritdoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        if (menu_load($node->getName())) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        $existing = menu_load($node->getName());

        if (!$existing) {
            $context->logCritical(sprintf("%s: does not exists", $node->getPath()));
        }

        return array_intersect_key($existing, self::$defaults) + self::$defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(NodeInterface $node, Context $context)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        if ($menu = $this->getExistingObject($node, $context)) {
            menu_delete($menu);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        // @todo
        throw new \Exception("Not implement (yet) - sorry dude.");
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node \USync\AST\Drupal\MenuNode */

        $object = ['menu_name' => $node->getName()];
        if ($node->hasChild('name')) {
            $object['title'] = (string)$node->getChild('name')->getValue();
        }
        if ($node->hasChild('description')) {
            $object['description'] = (string)$node->getChild('description')->getValue();
        }

        $object += self::$defaults;

        if ($node->shouldDropOnUpdate()) {
            menu_delete($object);
        }

        menu_save($object);

        return $node->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof MenuNode;
    }
}
