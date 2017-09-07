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
  <p><?php echo $this->text('This tool allows you to extract translatable strings from the source files.'); ?></p>
  <p><?php echo $this->text('Directories to be recursively scanned:'); ?></p>
  <ul>
    <?php foreach ($directories as $directory) { ?>
    <li><?php echo $this->e($directory); ?></li>
    <?php } ?>
  </ul>
  <p><?php echo $this->text('Supported file extensions: @extensions', array('@extensions' => implode(', ', array_keys($patterns)))); ?></p>
  <button class="btn btn-default" name="extract" value="1"><?php echo $this->text('Extract'); ?></button>
</form>
<?php echo $_job; ?>
