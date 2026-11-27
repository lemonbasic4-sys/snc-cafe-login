<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cashier-view.php';
require_once __DIR__ . '/../includes/order-void.php';
$user = snc_require_auth(['admin']);
$csrfToken = snc_csrf_token();
header('Cache-Control: no-store');
$voidError='';
$sncOrderRecordReadOnly=($_GET['view']??'')==='monitor';
if($sncOrderRecordReadOnly&&($_SERVER['REQUEST_METHOD']??'')!=='GET'){http_response_code(405);header('Allow: GET');exit('Live Operations order details are read-only.');}
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
    if(!snc_verify_csrf(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null)){http_response_code(403);$voidError='Session expired. Refresh before voiding.';}
    else {
        try {
            $viewId=filter_input(INPUT_GET,'order_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if(!$viewId||filter_var($_POST['order_id']??null,FILTER_VALIDATE_INT)!==$viewId)throw new InvalidArgumentException('Choose the order shown in these details.');
            $result=snc_void_order(snc_get_db(),$user,$_POST);snc_set_flash('success',$result['message'],'Order Voided');header('Location: order.php?order_id='.$viewId,true,303);exit;
        }catch(InvalidArgumentException $e){http_response_code(422);$voidError=$e->getMessage();}
        catch(RuntimeException $e){if(!($e instanceof PDOException)&&in_array($e->getCode(),[403,404,409],true)){http_response_code($e->getCode());$voidError=$e->getMessage();}else{http_response_code(503);$voidError='Unable to confirm the void. Refresh the details to check the result.';}}
    }
}
$flash=snc_pop_flash();
require __DIR__ . '/../includes/order-record.php';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Details | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-pos.css?v=2.5">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-void-request.js?v=1.1" defer></script>
  <script src="../assets/js/snc-void-order.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
</head><body<?= $flash?' data-snc-flash="'.snc_cashier_escape(json_encode($flash)).'"':'' ?>>
  <div class="snc-shell" id="snc-shell">
    <?php $adminPage = 'sales'; require __DIR__ . '/../includes/admin-shell.php'; ?>
    <main class="snc-dashboard"><?php $sncOwnerOrderView = true; require __DIR__ . '/../includes/order-details-view.php'; ?></main>
    </div>
  </div>
</body></html>
