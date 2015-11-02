<div class="row">
  <div class="col-md-4" id="usync-tree">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h2 class="panel-title"><?php echo t("Browser"); ?></h2>
      </div>
      <div class="panel-body">
        <?php echo render($tree); ?>
      </div>
    </div>
  </div>
  <div class="col-md-8" id="usync-tree-info">
    <h2><?php echo t("Information about @node"); ?></h2>
  </div>
</div>