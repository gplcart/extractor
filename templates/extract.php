<?php
/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<form method="post" class="col-md-6">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <p><?php echo $this->text('This tool allows you to extract translatable strings from the source files within the selected scope. Files with the following extensions will be scanned: @extensions', array('@extensions' => implode(', ', array_keys($patterns)))); ?></p>
  <div class="form-group">
    <label class="control-label"><?php echo $this->text('Scope'); ?></label>
    <select class="form-control" name="settings[scope]">
      <?php foreach ($scopes as $id => $scope) { ?>
      <option value="<?php echo $id; ?>">
        <?php echo $this->e($scope['name']); ?>
        <?php if ($id) { ?>
        (<?php echo $this->lower($this->text('Module')); ?>)
        <?php } ?>
      </option>
      <?php } ?>
    </select>
  </div>
  <button class="btn btn-default" name="extract" value="1"><?php echo $this->text('Extract'); ?></button>
</form>
<?php echo $_job; ?>
