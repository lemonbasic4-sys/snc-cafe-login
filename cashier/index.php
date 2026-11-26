<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = snc_require_auth(['cashier']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cashier POS | SNC 2018 Café &amp; Resto</title>
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <main>
    <h1>Cashier POS</h1>
    <p>Signed in as <strong><?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?>).</p>
    <p>Protected cashier workspace stub.</p>
    <form action="../logout.php" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit">Sign Out</button>
    </form>
  </main>
</body>
</html>
