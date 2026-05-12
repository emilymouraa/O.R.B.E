<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'ORBE';
$bodyClass = $bodyClass ?? '';

$_phpToast = null;
if (!empty($_SESSION['toast'])) {
    $_phpToast = $_SESSION['toast'];
    unset($_SESSION['toast']);
}

?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/favicon.ico">
</head>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">

<?php if ($_phpToast): ?>
<div id="php-toast-data" data-toast="<?= htmlspecialchars(json_encode($_phpToast)) ?>" style="display:none"></div>
<?php endif; ?>