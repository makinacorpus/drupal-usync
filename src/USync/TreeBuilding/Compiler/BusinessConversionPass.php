<?php

namespace USync\TreeBuilding\Compiler; 

use USync\AST\Node;
use USync\AST\Path;
use USync\Context;

/**
 * Replaces meaningfull path with their business implementations
 */
class BusinessConversionPass implements PassInterface
{
    /**
     * @var string[]
     */
    protected $map;

    public function __construct()
    {
        // Allow other modules to add stuff in there
        $this->map = module_invoke_all('usync_path_map');

        drupal_alter('usync_path_map', $this->map);

        foreach ($this->map as $pattern => $class) {
            if (!class_exists($class)) {
                unset($this->pathMap[$pattern]);
                trigger_error(sprintf("Class '%s' does not exist", $class), E_USER_ERROR);
            }
        }
    }

    public function execute(Node $node, Context $context)
    {
        $path = $node->getPath();

        foreach ($this->map as $pattern => $class) {
            $attributes = Path::match($path, $pattern);
            if ($attributes !== false) {

                /* @var $replacement \USync\AST\Drupal\DrupalNodeInterface */
                if ($node->isTerminal()) {
                    $replacement = new $class($node->getName(), $node->getValue());
                } else {
                    $replacement = new $class($node->getName());
                    // @todo fix this 2-step terminal check
                    if ($replacement->isTerminal()) {
                        $replacement = new $class($node->getName(), $node->getValue());
                    } else {
                        $replacement->mergeWith($node);
                    }
                }

                $replacement->setAttributes($node->getAttributes() + $attributes);

                $node->getParent()->replaceChild($node->getName(), $replacement);
                break;
            }
        }
    }
}
