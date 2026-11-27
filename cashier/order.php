<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cashier-view.php';
$user=snc_require_auth(['cashier']);
require_once __DIR__.'/../includes/void-requests.php';
$csrfToken=snc_csrf_token();$requestError='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(!snc_verify_csrf(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null))throw new RuntimeException('Session expired. Refresh and try again.',403);
        $viewId=filter_input(INPUT_GET,'order_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$viewId||filter_var($_POST['order_id']??null,FILTER_VALIDATE_INT)!==$viewId)throw new InvalidArgumentException('Choose the order shown in these details.');
        snc_submit_void_request(snc_get_db(),$user,$_POST);header('Location: order.php?order_id='.$viewId,true,303);exit;
    }catch(InvalidArgumentException $e){http_response_code(422);$requestError=$e->getMessage();}
    catch(RuntimeException $e){$code=!($e instanceof PDOException)&&in_array($e->getCode(),[403,404,409],true)?$e->getCode():503;http_response_code($code);$requestError=$code===503?'Unable to confirm the result. Refresh this order before trying again.':$e->getMessage();}
}
require __DIR__ . '/../includes/order-record.php';
snc_cashier_page_start('Order Details');
?>
<?php require __DIR__ . '/../includes/order-details-view.php'; ?>
<?php snc_cashier_page_end(); ?>
