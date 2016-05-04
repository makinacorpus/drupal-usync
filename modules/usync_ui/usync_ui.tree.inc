<?php
/**
 * @file
 * Tree UI.
 */

use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\Path;
use USync\AST\Processing\DrupalAttributesProcessor;
use USync\AST\Processing\ExpressionProcessor;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Visitor;
use USync\Context;
use USync\DrupalAdmin\AST\HtmlTreeBuilderVisitor;
use USync\TreeBuilding\GraphBuilder;

/**
 * Create the graph suitable for UI.
 *
 * Graph has already been executed once.
 *
 * @return \USync\AST\NodeInterface
 */
function usync_ui_graph_get($module = null, $source = null) {

  $sources = usync_module_list();
  // @todo
  $sources = ['usync:sample/blog.yml'];

  $graph = (new GraphBuilder($sources))->build();
  if (!$graph) {
    throw new \InvalidArgumentException("Invalid sources provided");
  }

  $context = new Context($graph);
  $visitor = new Visitor(Visitor::BOTTOM_TOP);
  $visitor->addProcessor(new ExpressionProcessor());
  $visitor->addProcessor(new DrupalAttributesProcessor());
  $visitor->addProcessor(new InheritProcessor());
  $visitor->execute($graph, $context);

  return $graph;
}

/**
 * Main tree callback.
 */
function usync_ui_tree($module = null, $source = null) {
  $build = [];

  $graph    = usync_ui_graph_get($module, $source);
  $context  = new Context($graph);

  $visitor = new HtmlTreeBuilderVisitor($build);
  $visitor->execute($graph, $context);

  return [
    '#attached' => [
      'css' => [drupal_get_path('module', 'usync_ui') . '/usync_ui.css'],
      'js'  => [
        drupal_get_path('module', 'usync_ui') . '/usync_ui.js',
        'misc/ajax.js',
      ],
    ],
    '#theme'    => 'usync_ui_tree',
    '#tree'     => $build,
  ];
}

/**
 * Pane information page/callback.
 */
function usync_ui_pane($mode = 'nojs') {

  if (!isset($_GET['path'])) {
    return MENU_NOT_FOUND;
  }

  if (empty($_GET['module'])) {
    $module = null;
  } else {
    $module = $_GET['module'];
  }
  if (empty($_GET['source'])) {
    $source = null;
  } else {
    $source = $_GET['source'];
  }

  $graph = usync_ui_graph_get($module, $source);
  if (!$graph) {
    return MENU_NOT_FOUND;
  }

  // Find node. Hard part :)
  $path = new Path($_GET['path']);
  $node = $path->find($graph);

  if (!$node) {
    return MENU_NOT_FOUND;
  }
  $node = reset($node);
  if (!$node instanceof DrupalNodeInterface) {
    return MENU_NOT_FOUND;
  }

  $view = usync_ui_info_pane($node);

  if ('ajax' === $mode) {
    return ajax_deliver([
      '#type' => 'ajax',
      '#commands' => [
        ajax_command_html('#usync-tree-info', drupal_render($view))
      ],
    ]);
  }

  return $view;
}

/**
 * Actionable stuff on a single node.
 */
function usync_ui_info_form($form, &$form_state, DrupalNodeInterface $node) {

  $loaders = usync_loader_list();
  $validLoaders = [];
  foreach ($loaders as $loader) {
    if ($loader->canProcess($node)) {
      $validLoaders[] = $loader;
    }
  }

  if (empty($validLoaders)) {
    return $form;
  }

  $form['actions']['#type'] = 'actions';

  if (!$node->shouldDelete()) {
    if (!$node->isTerminal()) {
      $form['actions']['merge'] = [
        '#type'   => 'submit',
        '#value'  => t("Merge tree"),
        '#submit' => ['usync_ui_info_form_submit_merge'],
      ];
      $form['actions']['merge_single'] = [
        '#type'   => 'submit',
        '#value'  => t("Merge"),
        '#submit' => ['usync_ui_info_form_submit_merge_single'],
      ];
    } else {
      $form['actions']['merge'] = [
        '#type'   => 'submit',
        '#value'  => t("Merge"),
        '#submit' => ['usync_ui_info_form_submit_merge'],
      ];
    }
  }

  $form['actions']['delete'] = [
    '#type'   => 'submit',
    '#value'  => t("Delete"),
    '#submit' => ['usync_ui_info_form_submit_delete'],
  ];

  return $form;
}

/**
 * Display info pane for a node.
 */
function usync_ui_info_pane(DrupalNodeInterface $node) {

  $loaders = usync_loader_list();
  $validLoaders = [];
  foreach ($loaders as $loader) {
    if ($loader->canProcess($node)) {
      $validLoaders[] = $loader;
    }
  }

  return [
    '#theme'    => 'usync_ui_info_pane',
    '#node'     => $node,
    '#loaders'  => $validLoaders,
    '#form'     => drupal_get_form('usync_ui_info_form', $node),
  ];
}
