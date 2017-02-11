<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<form method="post" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $this->prop('token'); ?>">
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="form-group">
        <div class="col-md-11 col-md-offset-1">
          <button class="btn btn-default" name="extract" value="1"><?php echo $this->text('Extract'); ?></button>
        </div>
      </div>
    </div>
  </div>
</form>
<?php if (!empty($job)) { ?>
<?php echo $job; ?>
<?php } ?>
