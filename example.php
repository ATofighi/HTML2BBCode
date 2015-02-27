<?php

error_reporting(E_ALL^E_NOTICE);
require './src/htmlParser.php';

$h2b = new htmlParser;
$h2b->setupMyCode();
?>
<form method="post">
	<textarea style="width:100%" name="bbcode" rows="10"><?php echo htmlspecialchars($_POST['bbcode']); ?></textarea>
	<input type="submit">
</form>
<?php

echo '<pre>'.$h2b->parse($_POST['bbcode']);