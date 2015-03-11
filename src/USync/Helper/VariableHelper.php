<?php

namespace USync\Helper;

use USync\Context;

class VariableHelper extends AbstractHelper
{
    public function getType()
    {
        return 'variable';
    }

    public function exists($path, Context $context)
    {
        $name = $this->getLastPathSegment($path);

        return array_key_exists($name, $GLOBALS['conf']);
    }

    public function fillDefaults($path, array $object, Context $context)
    {
        return null;
    }

    public function getExistingObject($path, Context $context)
    {
        return variable_get($this->getLastPathSegment($path));
    }

    public function deleteExistingObject($path, Context $context)
    {
        variable_del($this->getLastPathSegment($path));
    }

    public function synchronize($path, array $object, Context $context)
    {
        variable_set($this->getLastPathSegment($path), $object);
    }
}
