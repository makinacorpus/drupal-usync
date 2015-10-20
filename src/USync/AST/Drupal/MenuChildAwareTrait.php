<?php

namespace USync\AST\Drupal;

trait MenuChildAwareTrait
{
    /**
     * Get children
     *
     * @return \USync\AST\Drupal\MenuItemNode[]
     */
    public function getChildrenMenuItems()
    {
        // This is probably a bit ugly, but it works fine.
        // Note that $self only serves the purpose of letting Eclipse/PDT doing
        // working autocomplete, type hinting on $this does not work.

        /** @var $self \USync\AST\NodeInterface */
        $self = $this;

        $ret = [];

        if ($self->hasChild('item')) {
            $children = $self->getChild('item')->getChildren();

            foreach ($children as $child) {
                if ($child instanceof MenuItemNode) {
                    $ret[] = $child;
                }
            }
        }

        return $ret;
    }
}
