<?php
namespace Accela;

/** @var \Exception|NoPagePathsError|ServerComponentNotFoundError|ServerComponentDomainNotFoundError $e */
?>

<!DOCTYPE html>
<html>
<head>
<title>Error - Accela</title>
</head>

<body>
<div id="accela"></div>
<script>
<?php $_class = explode('\\', get_class($e)); $e_class = end($_class); ?>
const ACCELA = {}; ACCELA.modules = {}; ACCELA.hooks = {};
class ServerError extends Error {
  static {
    this.prototype.name = "<?php echo $e_class; ?>";
    this.prototype.isServerError = true;
    this.prototype.stackTrace = <?php echo json_encode($e->getTrace()); ?>;
    this.prototype.message = '<?php echo str_replace(['\\', '\''], ['\\\\', '\\\''], $e->getMessage()); ?>';
    this.prototype.file = '<?php echo str_replace('\\', '\\\\', $e->getFile()); ?>';
    this.prototype.line = '<?php echo str_replace('\\', '\\\\', $e->getLine()); ?>';
  }
}
<?php switch($e_class): ?>
<?php case "NoPagePathsError": ?>
class NoPagePathsError extends ServerError {
  constructor() {
    super();
    this.pagePath = '<?php echo $e->getMessage(); ?>';
    this.references = [
      ["Page Paths", "https://accela.in-green-spot.com/document/page-paths/"]
    ];
  }
}
ACCELA.serverError = new NoPagePathsError();

<?php break; case "ServerComponentDomainNotFoundError": ?>
  class ServerComponentDomainNotFoundError extends ServerError {
  constructor() {
    super();
    this.domainName = '<?php echo $e->domainName; ?>';
    this.references = [
      ["プラグイン", "https://accela.in-green-spot.com/document/plugins/"],
      ["サーバコンポーネント", "https://accela.in-green-spot.com/document/server-components/"]
    ];
  }
}
ACCELA.serverError = new ServerComponentDomainNotFoundError();

<?php break; case "ServerComponentNotFoundError": ?>
  class ServerComponentNotFoundError extends ServerError {
  constructor() {
    super();
    this.componentName = '<?php echo $e->componentName; ?>';
    this.references = [
      ["サーバコンポーネント", "https://accela.in-green-spot.com/document/server-components/"]
    ];
  }
}
ACCELA.serverError = new ServerComponentNotFoundError();

<?php break; default: ?>
ACCELA.serverError = new ServerError();
<?php endswitch; ?>
</script>
<script src="/assets/js/accela.js?__t=<?php echo isset($accela) ? $accela->getUtime() : time(); ?>" type="module"></script>
</body>
</html>
