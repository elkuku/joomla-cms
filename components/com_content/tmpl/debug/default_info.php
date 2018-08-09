<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<h2>Info</h2>
<ul>
    <li>
		<?php echo $this->meta['datetime'] ?>
    </li>
    <li>PHP Version: <?php echo $this->reports['info']['phpVersion'] ?></li>
    <li>Joomla! Version: <?php echo $this->reports['info']['joomlaVersion'] ?></li>
</ul>

<?php
dump($this->meta);
dump($this->reports);
