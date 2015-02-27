<?php

error_reporting(E_ALL^E_NOTICE);
require './html2bbcode.php';

$h2b = new html2bbcode;
$h2b->simpleBBCode('br', "[newline]", '', 'newline');
$h2b->simpleBBCode('p,div', "[newline]", '[newline]', 'newline');
$h2b->simpleBBCode('b,strong', '[b]', '[/b]', 'bold');
$h2b->simpleBBCode('i,em', '[i]', '[/i]', 'italic');
$h2b->simpleBBCode('u', '[u]', '[/u]', 'underline');
$h2b->simpleBBCode('s,strike,del', '[s]', '[/s]', 'strike');

$h2b->simpleBBCode('a[href]', '[email]', '[/email]', 'link', [
	['attr:href', 'regex', '#mailto:(.*)#si'],
	['attr:href', '=', 'mailto:+innerHTML']
]);
$h2b->simpleBBCode('a[href]', '[email={data-h2b-regex-attr:href-1}]', '[/email]', 'link', [
	['attr:href', 'regex', '#mailto:(.*)#si'],
]);

$h2b->simpleBBCode('a[href]', '[url]', '[/url]', 'link', [
	['attr:href', 'is', 'url'],
	['attr:href', '=', 'innerHTML']
]);
$h2b->simpleBBCode('a[href]', '[url={href}]', '[/url]', 'link', [
	['attr:href', 'is', 'url']
]);

$h2b->simpleBBCode('img[src]', '[img={width}x{height}]{src}[/img]', '', 'image', [
	['attr:src', 'is', 'url'],
	['attr:width', 'is', 'numeric'],
	['attr:height', 'is', 'numeric']
]);
$h2b->simpleBBCode('img[src]', '[img]{src}[/img]', '', 'image', [
	['attr:src', 'is', 'url']
]);

$h2b->simpleBBCode('li', "\n[*]", "", 'list-item');
$h2b->simpleBBCode('ul', "\n[list={type}]", "\n[/list]", 'list-item',[
	['attr:type', '!=', ''],
	['attr:type', 'in', ['a', 'A', 'i', 'I', '1']]
]);
$h2b->simpleBBCode('ol', "\n[list=1]", "\n[/list=1]", 'list-item');
$h2b->simpleBBCode('ul', "\n[list]", "\n[/list]", 'list-item');
?>
<form method="post">
	<textarea style="width:100%" name="bbcode" rows="10"><?php echo htmlspecialchars($_POST['bbcode']); ?></textarea>
	<input type="submit">
</form>
<?php

echo '<pre>'.$h2b->parse($_POST['bbcode']);