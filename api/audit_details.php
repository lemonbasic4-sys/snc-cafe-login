<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/audit.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function snc_audit_json(int $status,array $data): void {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=snc_current_user();if(!$user)snc_audit_json(401,['ok'=>false,'message'=>'Your session expired. Sign in again.']);
if(($user['role']??'')!=='admin')snc_audit_json(403,['ok'=>false,'message'=>'Only the owner can view the audit trail.']);
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){header('Allow: GET');snc_audit_json(405,['ok'=>false,'message'=>'Audit records are read-only.']);}
$source=is_string($_GET['source']??null)?$_GET['source']:'';$id=filter_input(INPUT_GET,'event_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
try{$record=snc_audit_detail(snc_get_db(),$source,$id);if(!$record)snc_audit_json(404,['ok'=>false,'message'=>'Audit record not found.']);snc_audit_json(200,['ok'=>true,'record'=>$record]);}
catch(InvalidArgumentException $e){snc_audit_json(400,['ok'=>false,'message'=>$e->getMessage()]);}
catch(Throwable $e){snc_audit_json(503,['ok'=>false,'message'=>'Audit details are unavailable. Please try again.']);}
