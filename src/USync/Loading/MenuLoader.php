<?php

namespace USync\Loading;

use USync\AST\Drupal\MenuNode;
use USync\AST\NodeInterface;
use USync\Context;

class MenuLoader extends AbstractLoader
{
    static private $defaults = [
        'menu_name'   => '',
        'title'       => 'node_content',
        'description' => null,
    ];

    public function init()
    {
        parent::init();

        // Also tests on function existence, someone might have the same idea
        if (!module_exists('menu') && !function_exists('menu_load')) {
            require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'usync') . '/includes/polyfill-menu-module.php';
        }
    }

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
            $context->log(sprintf("%s: deleting menu and children", $node->getPath()));
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
