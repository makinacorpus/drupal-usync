<?php

namespace USync\DrupalAdmin\AST; 

use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\Node;
use USync\AST\Visitor;
use USync\Context;

class HtmlTreeBuilderVisitor extends Visitor
{
    /**
     * @var array
     */
    protected $renderArray;

    /**
     * Default constructor
     *
     * @param array $renderArray
     *   Output build
     */
    public function __construct(&$renderArray)
    {
        $this->renderArray = &$renderArray;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Node $node, Context $context)
    {
        $current = &$this->renderArray;
        $current['#prefix'] = '<ul>';
        $current['#suffix'] = '</ul>';

        $this->buildTree($node, $context, $current['content']);
    }

    /**
     * Execute and build the tree at the same time
     *
     * @param Node $node
     * @param Context $context
     * @param array $current
     */
    protected function buildTree(Node $node, Context $context, &$current)
    {
        $continue = false;

        $this->executeProcessorsOnNode($node, $context);

        $current['#prefix'] = '<li>';
        if ($node instanceof DrupalNodeInterface) {
            $current['node']['content'] = [
                '#theme' => 'link',
                '#path' => 'admin/structure/usync/pane/nojs',
                '#text' => $node->getName(),
                '#options' => [
                    'html' => false,
                    'attributes' => ['class' => ['use-ajax']],
                    'query' => [
                        'path' => $node->getPath(),
                        'cache' => time(), // Cache killer
                    ],
                ],
            ];
        } else {
            $current['node']['#markup'] = $node->getName();
        }
        $current['#suffix'] = '</li>';

        if (!$node->isTerminal()) {

            $children = [];

            foreach ($node->getChildren() as $child) {
                $continue |= $this->buildTree($child, $context, $children[$child->getName()]);
            }

            if ($continue) {
                $current['tree']['#prefix'] = '<ul>';
                $current['tree']['#suffix'] = '</ul>';
                $current['tree']['content'] = $children;
            }
        }

        return $continue || $node instanceof DrupalNodeInterface;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeBottomTop(Node $node, Context $context)
    {
        throw new \Exception("Tree builder visitor only supports top to bottom traversal.");
    }
}
