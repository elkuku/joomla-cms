<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<h2>Deprecated</h2>

<ul>
	<?php foreach ($this->reports['deprecated']['messages'] as $item): ?>
        <li><?php echo $item['message'] ?></li>
	<?php endforeach; ?>s
</ul>
