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
<h2>Language Files</h2>

<table>
    <tr>
        <th>Extension</th>
        <th>File(s) loaded</th>
    </tr>
	<?php foreach ($this->reports['languageFiles'] as $extension => $files): ?>
        <tr>
            <td><?php echo $extension ?></td>
            <td>
                <ul>
					<?php foreach ($files as $file => $status): ?>
                        <li class="alert-<?php echo $status ? 'success' : 'warning' ?>">
							<?php echo HTMLHelper::_('debug.xdebuglink', $file) ?>
                        </li>
					<?php endforeach; ?>
                </ul>
            </td>
        </tr>
	<?php endforeach; ?>
</table>
