<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<h1>Last Ten Requests</h1>

<table class="table">
	<tr>
		<th>IP</th>
		<th>Method</th>
		<th>URI</th>
		<th>Time</th>
		<th>Token</th>
	</tr>
	<?php $this->uri->setVar('search', '') ?>
	<?php foreach ($this->data as $item): ?>
    <?php $this->uri->setVar('id', $item['id']) ?>
	<tr>
		<td><?php echo $item['ip'] ?></td>
		<td><?php echo $item['method'] ?></td>
		<td><?php echo $item['uri'] ?></td>
		<td><?php echo $item['datetime'] ?></td>
		<td>
            <a href="<?php echo $this->uri ?>"><?php echo substr($item['id'], 0, 7) ?></a>
        </td>
	</tr>
	<?php endforeach; ?>
</table>

