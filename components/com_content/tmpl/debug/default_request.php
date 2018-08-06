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
