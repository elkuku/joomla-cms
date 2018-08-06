<h2>Language Strings</h2>

<?php dump($this->reports['languageStrings']) ?>
<ul>
	<?php foreach ($this->reports['languageStrings']['data'] as $error): ?>
        <li>
			<?php echo $error ?>
        </li>
	<?php endforeach; ?>
</ul>
