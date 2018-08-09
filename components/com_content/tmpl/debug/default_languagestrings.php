<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;

?>
<h2>Language Strings</h2>

<pre>
<?php foreach ($this->data as $file => $strings): ?>
## <?php echo HTMLHelper::_('debug.xdebuglink', $file) . PHP_EOL?>
<?php foreach ($strings as $string): ?>
<?php echo $string . PHP_EOL?>
<?php endforeach; ?>
<?php echo PHP_EOL ?>
<?php endforeach; ?>
</pre>
