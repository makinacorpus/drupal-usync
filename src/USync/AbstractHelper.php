<?php

namespace USync;

/**
 * Abstract helper
 */
abstract class zzzzAbstractHelper extends AbstractContextAware
{
    /**
     * Update
     */
    const HOOK_UPDATE = 'update';

    /**
     * Insert
     */
    const HOOK_INSERT = 'insert';

    /**
     * @var string
     */
    protected $component;

    /**
     * Default constructor
     *
     * @param string $component
     */
    public function __construct($component)
    {
        $this->component = $component;
    }

    /**
     * Invoke a hook to modules to allow them to alter data
     *
     * @param string $hook
     * @param string $name
     * @param array $object
     */
    protected function alter($hook, $name, array &$object)
    {
        drupal_alter('usync_' . $hook . '_' . $this->component, $object, $name);
    }
}
