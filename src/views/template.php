<!DOCTYPE html>
<?php if($accela->lang): ?>
<html lang="<?php echo $accela->lang; ?>">
<?php else: ?>
<html>
<?php endif; ?>
<head>
<?php echo $accela->outputGenerator->htmlHeader($page); ?>
</head>
<body>
<?php $accela->hook->exec("body-start"); ?>
<div id="accela"></div>
<script>const ACCELA = <?php echo json_encode($accela->outputGenerator->initialData($page)); ?>; ACCELA.modules = {}; ACCELA.hooks = {beforeMovePage: () => {<?php $accela->hook->exec("before-move-page"); ?>}, afterMovePage: () => {<?php $accela->hook->exec("after-move-page"); ?>}};</script>
<script src="/assets/js/accela.js?__t=<?php echo $accela->getUtime(); ?>" type="module"></script>
<?php $accela->hook->exec("body-end"); ?>
</body>
</html>
