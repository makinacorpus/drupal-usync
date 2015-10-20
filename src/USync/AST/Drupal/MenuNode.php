<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class MenuNode extends Node implements DrupalNodeInterface
{
    use DrupalNodeTrait;

    use MenuChildAwareTrait;

    /**
     * Get menu human readable name
     *
     * @return string
     */
    public function getHumanName()
    {
        if ($this->hasChild('name')) {
            return (string)$this->getChild('name')->getValue();
        }
        return $this->getName();
    }

    /**
     * Should this menu drop all children on update
     *
     * @return boolean
     */
    public function shouldDropOnUpdate()
    {
        if ($this->hasChild('drop_on_update')) {
            return (boolean)$this->getChild('drop_on_update')->getValue();
        }
        return false;
    }
}
