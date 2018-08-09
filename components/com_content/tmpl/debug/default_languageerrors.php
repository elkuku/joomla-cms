<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<h2>Language Errors</h2>

<ul>
	<?php foreach ($this->data as $item): ?>
        <li>
			<?php echo $item ?>
        </li>
	<?php endforeach; ?>
</ul>
