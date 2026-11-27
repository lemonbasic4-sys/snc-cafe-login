<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/live-operations.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function snc_operations_json(int $code,array $data): void {http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=snc_current_user();if(!$user)snc_operations_json(401,['ok'=>false,'message'=>'Sign in again to view Live Operations.']);if($user['role']!=='admin')snc_operations_json(403,['ok'=>false,'message'=>'Only the owner can view Live Operations.']);
if($_SERVER['REQUEST_METHOD']!=='GET'){header('Allow: GET');snc_operations_json(405,['ok'=>false,'message'=>'Live Operations is read-only.']);}
try{snc_operations_json(200,['ok'=>true]+snc_operations_report(snc_get_db(),$_GET));}
catch(InvalidArgumentException $e){snc_operations_json(400,['ok'=>false,'message'=>$e->getMessage()]);}
catch(Throwable $e){snc_operations_json(503,['ok'=>false,'message'=>'Live updates are temporarily unavailable.']);}
