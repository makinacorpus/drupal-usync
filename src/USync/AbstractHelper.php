<?php

namespace USync;

/**
 * Abstract helper
 */
abstract class AbstractHelper
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
     * @var \USync\Context
     */
    protected $context;

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
     * Set context
     *
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Get context
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int $level
     */
    final protected function log($message, $level = E_USER_NOTICE)
    {
        trigger_error(get_class($this) . ': ' . $message, $level);
    }

    /**
     * Log warning message
     *
     * @param string $message
     */
    final protected function logWarning($message)
    {
        $this->log($message, E_USER_WARNING);

        if ($this->context->breakOn <= Config::BREAK_WARNING) {
            throw new \RuntimeException("Error level breakage");
        }
    }

    /**
     * Log data loss warning
     *
     * @param string $message
     */
    final protected function logDataloss($message)
    {
        $this->log($message, E_USER_ERROR);

        if ($this->context->breakOn <= Config::BREAK_DATALOSS) {
            throw new \RuntimeException("Error level breakage");
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     */
    final protected function logError($message)
    {
        $this->log($message, E_USER_ERROR);

        if ($this->context->breakOn <= Config::BREAK_ERROR) {
            throw new \RuntimeException("Error level breakage");
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     */
    final protected function logCritical($message)
    {
        $this->log($message, E_USER_ERROR);

        throw new \RuntimeException("Critical error breakage");
    }

    /**
     * Delete a single object
     *
     * @param string $name
     */
    abstract public function delete($name);

    /**
     * Delete a list of object
     *
     * @param string[] $nameList
     */
    public function deleteAll($nameList)
    {
        foreach ($nameList as $name) {
            $this->delete($name);
        }
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

    /**
     * Synchronise a single object
     *
     * @param string $name
     * @param array $object
     */
    abstract protected function sync($name, array $object);

    /**
     * Synchronize a list of objects
     *
     * @param array[] $objectList
     */
    protected function syncAll(array $objectList)
    {
        foreach ($objectList as $name => $object) {
            $this->sync($name, $object);
        }
    }

    /**
     * Populate every entry using the inherit keyword of the top level items
     *
     * @param Config $config
     */
    protected function populateInheritance(Config $config)
    {
        $sorted = array();
        $orphans = array();

        foreach ($config as $key => $object) {

            if (isset($object['inherit'])) {
                // Generic inheritance processing
                if (!is_string($object['inherit'])) {
                    $this->logCritical(sprintf("%s: malformed inherit directive", $key));
                }
                // @todo Deal with circular dependencies.
                if (!isset($config[$object['inherit']])) {
                    $this->logCritical(sprintf("%s: cannot inherit from non existing: %s", $key, $object['inherit']));
                }
                if ($key === $object['inherit']) {
                    $this->logCritical(sprintf("%s: cannot inherit from itself", $key));
                }
                $orphans[$key] = $object['inherit'];

            } else {
                $sorted[$key] = array();
            }
        }

        while (!empty($orphans)) {
            $count = count($orphans);
            foreach ($orphans as $key => $parent) {
                if (isset($sorted[$parent])) {
                    $sorted[$parent][] = $key;
                    $sorted[$key] = array();
                    unset($orphans[$key]);
                }
            }
            if (count($orphans) === $count) {
                $this->logCritical(sprintf("Circular dependency detected"));
            }
        }

        foreach (array_filter($sorted) as $parent => $children) {
            foreach ($children as $key) {
                $config->mergeOver($key, $parent);
            }
        }
    }

    /**
     * Process the given config object as a section
     *
     * @param Config $config
     */
    public function processSection(Config $config)
    {
        $this->populateInheritance($config);
        $this->syncAll($config->getAll());
    }
}
