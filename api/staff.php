<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/staff.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function snc_staff_json(int $status,array $body): void {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;}
$user=snc_current_user();
if(!$user)snc_staff_json(401,['ok'=>false,'message'=>'Your session expired. Sign in again.']);
if(($user['role']??'')!=='admin')snc_staff_json(403,['ok'=>false,'message'=>'Only the owner can manage staff.']);
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');snc_staff_json(405,['ok'=>false,'message'=>'Method not allowed.']);}
if(!snc_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??null))snc_staff_json(403,['ok'=>false,'message'=>'Session expired. Refresh and try again.']);
$data=json_decode(file_get_contents('php://input')?:'',true);
if(!is_array($data))snc_staff_json(400,['ok'=>false,'message'=>'Invalid request.']);
try {
    $result=snc_staff_mutate(snc_get_db(),$user,$data);snc_set_flash('success',$result['message'],'Staff Updated');snc_staff_json(200,$result);
}catch(InvalidArgumentException $e){snc_staff_json(422,['ok'=>false,'message'=>$e->getMessage()]);}
catch(PDOException $e){snc_staff_json((string)$e->getCode()==='23000'?409:503,['ok'=>false,'message'=>(string)$e->getCode()==='23000'?'That username is already in use. Choose another.':'Unable to save staff details. Please try again.']);}
catch(RuntimeException $e){if(in_array($e->getCode(),[403,409],true))snc_staff_json($e->getCode(),['ok'=>false,'message'=>$e->getMessage()]);snc_staff_json(503,['ok'=>false,'message'=>'Unable to save staff details. Please try again.']);}
catch(Throwable $e){snc_staff_json(503,['ok'=>false,'message'=>'Unable to save staff details. Please try again.']);}
