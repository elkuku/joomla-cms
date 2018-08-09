<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<h2>Request</h2>

<h3>GET Parameters</h3>
<?php dump($this->reports['request']['$_GET']); ?>

<h3>POST Parameters</h3>
<?php dump($this->reports['request']['$_POST']); ?>

<h3>SESSION Parameters</h3>
<?php dump($this->reports['request']['$_SESSION']); ?>

<h3>COOKIE Parameters</h3>
<?php dump($this->reports['request']['$_COOKIE']); ?>

<h3>SERVER Parameters</h3>
<?php dump($this->reports['request']['$_SERVER']); ?>
