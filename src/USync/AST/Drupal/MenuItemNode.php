<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class MenuItemNode extends Node implements DrupalNodeInterface
{
    use DrupalNodeTrait;

    use MenuChildAwareTrait;

    /**
     * Get link path
     *
     * @return string
     */
    public function getLinkPath()
    {
        if ($this->hasChild('path')) {
            return (string)$this->getChild('path')->getValue();
        }
        return '';
    }

    /**
     * Get link title
     *
     * @return string
     */
    public function getLinkTitle()
    {
        if ($this->hasChild('name')) {
            return (string)$this->getChild('name')->getValue();
        }
        return '';
    }

    /**
     * Get parent menu item if exists
     *
     * @return \USync\AST\Drupal\MenuItemNode
     */
    public function getParentMenuItem()
    {
        /** @var $self \USync\AST\NodeInterface */
        $self = $this;

        $parent = $self->getParent()->getParent();

        if ($parent instanceof MenuItemNode) {
            return $parent;
        }
    }

    /**
     * Get weight
     *
     * @return int
     */
    public function getWeight()
    {
        return (int)$this->getName();
    }

    /**
     * Get menu name
     *
     * @return string
     */
    public function getMenuName()
    {
        return $this->getAttribute('menu');
    }
}
