<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

?>
<div style="border: 1px solid red; padding: 5px;">

    <div>
        <a href="<?php echo $this->uri ?>">Current</a>
        <a href="<?php echo $this->uri . '&search=lastTen'?>">Last 10</a>
    </div>

    <div style="border: 1px solid green; padding: 5px;">

        <h1>! WIP ! Joomla! Debug ! WIP !</h1>

        <!-- Navigation -->
        <ul>
			<?php foreach ($this->reports as $group => $report) : ?>
				<?php $this->uri->setVar('panel', $group) ?>
                <li class="<?php echo $this->panel === $group ? 'active' : '' ?>">
                    <a href="<?php echo $this->uri ?>">
						<?php echo $group ?>
						<?php if (isset($report['count']) and $report['count']): ?>
							<?php echo " ({$report['count']})" ?>
						<?php endif; ?>
                    </a>
                </li>
			<?php endforeach; ?>
        </ul>
    </div>

    <!-- Panel -->
    <div style="border: 1px solid blue; padding: 5px;">
		<?php echo $this->loadTemplate($this->panel) ?>
    </div>

</div>
