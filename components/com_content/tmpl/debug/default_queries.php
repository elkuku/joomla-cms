<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<h2>Database</h2>

<?php echo sprintf('%d database queries executed', $this->reports['queries']['count']) ?>

<?php echo implode('', $this->data) ?>
