<h2>Database</h2>

<?php echo sprintf('%d database queries executed', $this->reports['queries']['count']) ?>

<?php echo implode('', $this->data) ?>
