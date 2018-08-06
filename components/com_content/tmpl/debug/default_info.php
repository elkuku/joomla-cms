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
