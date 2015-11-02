<div class="panel panel-default">
  <div class="panel-heading">
    <h2 class="panel-title"><?php echo $node->getName(); ?></h2>
  </div>
  <div class="panel-body">

    <?php if ($form): ?>
      <?php echo render($form); ?>
    <?php endif; ?>

    <hr/>

    <h3><?php echo t("Node status"); ?></h3>

    <dl class="dl-horizontal">

      <dt>Status</dt>
      <dd><?php echo render($status); ?></dd>

      <?php if (!$node->shouldDelete()): ?>
        <dt>Behavior</dt>
        <dd>
          <?php if ($node->isMerge()): ?>
            <span class="text-danger"><strong><?php echo t("Merge with existing"); ?></strong></span>
          <?php else: ?>
            <?php echo t("Replace existing"); ?>
          <?php endif; ?>
        <dt>Dirty</dt>
        <dd>
          <?php if ($node->isDirty()): ?>
            <span class="text-danger"><strong><?php echo t("Yes"); ?></strong></span>
          <?php else: ?>
            <?php echo t("No"); ?>
          <?php endif; ?>
        </dd>
      <?php endif; ?>

    </dl>

    <hr/>

    <dl class="dl-horizontal">
      <dt>Path</dt>
      <dd><code><?php echo $node->getPath(); ?></code></dd>
      <dt>Class</dt>
      <dd><code><?php echo get_class($node); ?></code></dd>
    </dl>

    <?php if ($attributes = $node->getAttributes()): ?>
      <hr/>

      <h3><?php echo t("Attributes"); ?></h3>

      <dl class="dl-horizontal">
        <?php foreach ($attributes as $name => $value): ?>
          <dt><?php echo $name; ?></dt>
          <dd><?php echo $value; ?></dd>
        <?php endforeach; ?>
      </dl>
    <?php endif; ?>

    <hr/>

    <?php if ($loaders): ?>
      <?php foreach ($loaders as $loader): ?>

        <?php if ($loader instanceof \USync\Loading\VerboseLoaderInterface): ?>
          <h3><?php echo $loader->getLoaderName(); ?></h3>
        <?php else: ?>
          <h3><?php echo $loader->getType(); ?></h3>
        <?php endif; ?>

        <dl class="dl-horizontal">

          <?php if ($loader instanceof \USync\Loading\VerboseLoaderInterface): ?>
            <?php if ($description = $loader->getLoaderDescription()): ?>
              <dt><?php echo t("Description"); ?></dt>
              <dd><em><?php echo $description; ?></em></dd>
            <?php endif; ?>
            <?php if ($label = $loader->getNodeName($node)): ?>
              <dt><?php echo t("Name"); ?></dt>
              <dd><em><?php echo $label; ?></em></dd>
            <?php endif; ?>
          <?php endif; ?>

          <dt>Class</dt>
          <dd><code><?php echo get_class($loader); ?></code></dd>

          <?php if ($dependencies = $loader->getExtractDependencies($node, $context)): ?>
            <dt><?php echo t("Dependencies"); ?></dt>
            <dd>
              <ul class="dependencies list-unstyled">
                <?php foreach ($dependencies as $path): ?>
                  <li><code><?php echo $path; ?></code></li>
                <?php endforeach; ?>
              </ul>
            </dd>
          <?php endif; ?>
        </dl>

      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>