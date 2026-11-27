<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/void-requests.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function snc_request_json(int $status,array $body): void {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;}
$user=snc_current_user();if(!$user)snc_request_json(401,['ok'=>false,'message'=>'Your session expired. Sign in again.']);
if(!in_array($user['role'],['cashier','admin'],true))snc_request_json(403,['ok'=>false,'message'=>'Your role cannot manage void requests.']);
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');snc_request_json(405,['ok'=>false,'message'=>'Use the request confirmation form.']);}
if(!snc_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??null))snc_request_json(403,['ok'=>false,'message'=>'Session expired. Refresh and try again.']);
$data=json_decode(file_get_contents('php://input')?:'',true);if(!is_array($data))snc_request_json(400,['ok'=>false,'message'=>'Invalid request.']);
try{
    $action=$data['action']??'';
    if($action==='request')$result=snc_submit_void_request(snc_get_db(),$user,$data);
    elseif($action==='review')$result=snc_review_void_request(snc_get_db(),$user,$data);
    else throw new InvalidArgumentException('Choose a valid action.');
    snc_request_json(200,$result);
}catch(InvalidArgumentException $e){snc_request_json(422,['ok'=>false,'message'=>$e->getMessage()]);}
catch(RuntimeException $e){$code=!($e instanceof PDOException)&&in_array($e->getCode(),[403,404,409],true)?$e->getCode():503;snc_request_json($code,['ok'=>false,'message'=>$code===503?'Unable to confirm the result. Retry the same submission or refresh the details.':$e->getMessage()]);}
catch(Throwable $e){snc_request_json(503,['ok'=>false,'message'=>'Unable to confirm the result. Retry the same submission or refresh the details.']);}
