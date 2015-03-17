<?php

namespace USync;

use USync\AST\Node;
use USync\AST\Processing\DrupalProcessor;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Visitor;
use USync\Helper\FieldHelper;
use USync\Helper\FieldInstanceHelper;
use USync\Helper\HelperInterface;
use USync\Helper\NodeEntityHelper;
use USync\Helper\VariableHelper;
use USync\Helper\ViewModeHelper;

class Runner
{
    /**
     * @var \USync\Helper\HelperInterface[]
     */
    protected $helpers = array();

    /**
     * Default constructor
     */
    public function __construct()
    {
        // @todo Make this better
        // Content types first.
        // @todo Fetch a map of used fields.
        // Always process fields first.
        // @todo Do not import non used fields.
        $instanceHelper = new FieldInstanceHelper();
        $this->helpers = array(
            'field.%'            => new FieldHelper($instanceHelper),
            'entity.node.%'      => new NodeEntityHelper(),
            'entity.%.%.field.%' => $instanceHelper,
            'view.%.%.%'         => new ViewModeHelper(),
            'variable.%'         => new VariableHelper(),
        );
    }

    /**
     * Run changes following the given configuration
     *
     * @param Config $config
     */
    public function run(Context $context)
    {
        $config = $context->getGraph();

        $visitor = new Visitor();
        $visitor->addProcessor(new InheritProcessor());
        $visitor->execute($config, $context);

        $visitor = new Visitor();
        $visitor->addProcessor(new DrupalProcessor($this->helpers));
        $visitor->execute($config, $context);

        // Sorry.
        menu_rebuild();
    }
}
