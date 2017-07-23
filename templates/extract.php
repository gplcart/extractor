<?php
/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<form method="post">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <button class="btn btn-default" name="extract" value="1"><?php echo $this->text('Extract'); ?></button>
</form>
<?php echo $_job; ?>
