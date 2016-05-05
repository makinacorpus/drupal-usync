<?php

namespace USync\Loading;

use USync\AST\Drupal\MenuItemNode;
use USync\AST\NodeInterface;
use USync\Context;

class MenuItemLoader extends AbstractLoader
{
    static private $defaults = [
        'menu_name'   => '',
        'link_title'  => 'Did you forgot to set a name ?',
        'options'     => [],
        'hidden'      => false,
        'expanded'    => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'menu_item';
    }

    /**
     * Find menu item identifier
     *
     * @param NodeInterface $node
     * @param Context $context
     *
     * @return int
     */
    protected function findMenuItemId(NodeInterface $node, Context $context)
    {
        /* @var $node \USync\AST\Drupal\MenuItemNode */

        // SERIOUS FIXME
        // Sorry, from this point I am making the same error as features...
        // ... meaning using the path as identifier, but except by doing a
        // mapping table, I'm not sure I can do this another way; I should
        // at least use the complete path

        return db_query("SELECT mlid FROM {menu_links} WHERE link_title = :title AND menu_name = :menu", [
            ':title'  => $node->getLinkTitle(),
            ':menu'   => $node->getMenuName(),
        ])->fetchField();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        if ($this->findMenuItemId($node, $context)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        $existing = null;

        if ($mlid = $this->findMenuItemId($node, $context)) {
            $existing = menu_link_load($mlid);
        }

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
        /* @var $node \USync\AST\Drupal\MenuItemNode */
        $ret = [];

        $parent = $node->getParentMenuItem();
        if ($parent) {
            $ret[] = $parent->getPath();
        } else {
            $ret[] = 'menu.' . $node->getMenuName();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        if ($mlid = $this->findMenuItemId($node, $context)) {
            menu_link_delete($mlid);
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
        /* @var $node \USync\AST\Drupal\MenuItemNode */

        $object = [
            'menu_name'   => $node->getMenuName(),
            'customized'  => 1,
            'weight'      => $node->getPosition(),
        ];
        if ($node->hasChild('name')) {
            $object['link_title'] = (string)$node->getChild('name')->getValue();
        }
        if ($node->hasChild('path')) {
            $object['link_path'] = (string)$node->getChild('path')->getValue();
        }
        if ($node->hasChild('expanded')) {
            $object['expanded'] = (int)(bool)$node->getChild('expanded')->getValue();
        }
        if ($node->hasChild('hidden')) {
            $object['hidden'] = (int)(bool)$node->getChild('hidden')->getValue();
        }
        if ($node->hasChild('options')) {
            $object['options'] = (array)$node->getChild('options')->getValue();
            if (!empty($object['options'])) {
                // @todo Should merge with existing, maybe, or defaults ?
            }
        }
        $object += self::$defaults;

        if ($mlid = $this->findMenuItemId($node, $context)) {
            $object['mlid'] = $mlid;
        }

        // Find parent - no matter how hard it is.
        // First one is "menu", second one is the real parent.
        $parent = $node->getParentMenuItem();
        if ($parent) {
            if ($plid = $parent->getDrupalIdentifier()) {
                $object['plid'] = $plid;
            }
        }
        if (empty($object['plid'])) {
            $object['plid'] = 0;
        }

        // Phoque zate.
        $object['hidden'] = (int)(bool)$object['hidden'];
        $object['expanded'] = (int)(bool)$object['expanded'];

        $id = menu_link_save($object);
        // It seems that, sometime, this doesn't get called...
        _menu_update_parental_status($object);

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof MenuItemNode;
    }
}
