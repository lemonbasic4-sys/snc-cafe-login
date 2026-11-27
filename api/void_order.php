<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/order-void.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function snc_void_json(int $status,array $body): void {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;}
$user=snc_current_user();if(!$user)snc_void_json(401,['ok'=>false,'message'=>'Your session expired. Sign in again.']);
if(($user['role']??'')!=='admin')snc_void_json(403,['ok'=>false,'message'=>'Only the owner can void orders.']);
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');snc_void_json(405,['ok'=>false,'message'=>'Use the confirmation form to void an order.']);}
if(!snc_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??null))snc_void_json(403,['ok'=>false,'message'=>'Session expired. Refresh and try again.']);
$data=json_decode(file_get_contents('php://input')?:'',true);if(!is_array($data))snc_void_json(400,['ok'=>false,'message'=>'Invalid request.']);
try{$result=snc_void_order(snc_get_db(),$user,$data);snc_set_flash('success',$result['message'],'Order Voided');snc_void_json(200,$result);}
catch(InvalidArgumentException $e){snc_void_json(422,['ok'=>false,'message'=>$e->getMessage()]);}
catch(PDOException $e){snc_void_json((string)$e->getCode()==='23000'?409:503,['ok'=>false,'message'=>(string)$e->getCode()==='23000'?'This confirmation request was already used. Refresh the order details.':'Unable to confirm the void. Retry with the same reason to check its result.']);}
catch(RuntimeException $e){if(!($e instanceof PDOException)&&in_array($e->getCode(),[403,404,409],true))snc_void_json($e->getCode(),['ok'=>false,'message'=>$e->getMessage()]);snc_void_json(503,['ok'=>false,'message'=>'Unable to confirm the void. Retry with the same reason to check its result.']);}
catch(Throwable $e){snc_void_json(503,['ok'=>false,'message'=>'Unable to confirm the void. Retry with the same reason to check its result.']);}
