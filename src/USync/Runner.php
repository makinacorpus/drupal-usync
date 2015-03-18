<?php

namespace USync;

use USync\AST\Node;
use USync\AST\Processing\DrupalProcessor;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Processing\StatProcessor;
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
     * @var \USync\AST\Processing\StatProcessor
     */
    protected $statProcessor;

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
            new FieldHelper($instanceHelper),
            new NodeEntityHelper(),
            $instanceHelper,
            new ViewModeHelper(),
            new VariableHelper(),
        );

        $this->statProcessor = new StatProcessor();
    }

    /**
     * Get stat processor
     *
     * @return \USync\AST\Processing\StatProcessor
     */
    public function getStatProcessor()
    {
        return $this->statProcessor;
    }

    /**
     * Run changes following the given configuration
     *
     * @param \USync\Context $config
     * @param boolean $doProcess
     *   Effectively writes Drupal data
     */
    public function run(Context $context, $doProcess = true)
    {
        $config = $context->getGraph();

        $this->statProcessor->reset();

        $visitor = new Visitor();
        $visitor->addProcessor(new InheritProcessor());
        $visitor->addProcessor($this->statProcessor);
        $visitor->execute($config, $context);

        if ($doProcess) {
            $visitor = new Visitor();
            $visitor->addProcessor(new DrupalProcessor($this->helpers));
            $visitor->execute($config, $context);
            menu_rebuild(); // Sorry.
        }
    }
}
