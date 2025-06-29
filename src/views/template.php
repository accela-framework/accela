<!DOCTYPE html>
<?php if(defined('HTML_LANG')): ?>
<html lang="<?php echo HTML_LANG; ?>">
<?php else: ?>
<html>
<?php endif; ?>
<head>
<?php echo Accela\getHeaderHtml($page); ?>
</head>
<body>
<?php Accela\Hook::exec("body-start"); ?>
<div id="accela"></div>
<script>const ACCELA = <?php echo json_encode(Accela\getInitialData($page)); ?>; ACCELA.modules = {}; ACCELA.hooks = {beforeMovePage: () => {<?php Accela\Hook::exec("before-move-page"); ?>}, afterMovePage: () => {<?php Accela\Hook::exec("after-move-page"); ?>}};</script>
<script src="/assets/js/accela.js?__t=<?php echo Accela\getUtime(); ?>" type="module"></script>
<?php Accela\Hook::exec("body-end"); ?>
</body>
</html>
