<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/inventory.php';
header('Content-Type: application/json; charset=UTF-8'); header('Cache-Control: no-store');
function snc_inventory_json(int $status, array $data): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
$user = snc_current_user();
if (!$user) snc_inventory_json(401, ['ok'=>false,'message'=>'Your session expired. Sign in again.']);
if (($user['role'] ?? '') !== 'admin') snc_inventory_json(403, ['ok'=>false,'message'=>'Only the owner can manage inventory.']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); snc_inventory_json(405,['ok'=>false,'message'=>'Method not allowed.']); }
if (!snc_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) snc_inventory_json(403,['ok'=>false,'message'=>'Session expired. Refresh and try again.']);
$data=json_decode(file_get_contents('php://input') ?: '',true);
if (!is_array($data)) snc_inventory_json(400,['ok'=>false,'message'=>'Invalid request.']);
try {
    $result=snc_inventory_mutate(snc_get_db(),$user,$data);
    snc_set_flash('success',$result['message'],'Inventory Updated');
    snc_inventory_json(200,$result);
} catch (InvalidArgumentException $e) { snc_inventory_json(422,['ok'=>false,'message'=>$e->getMessage()]); }
catch (PDOException $e) {
    if ((string)$e->getCode()==='23000') snc_inventory_json(409,['ok'=>false,'message'=>'This ingredient name or stock request already exists. Refresh before trying again.']);
    snc_inventory_json(503,['ok'=>false,'message'=>'Unable to save inventory. Please try again.']);
} catch (RuntimeException $e) {
    if ($e->getCode()===409) snc_inventory_json(409,['ok'=>false,'message'=>$e->getMessage()]);
    snc_inventory_json(503,['ok'=>false,'message'=>'Unable to save inventory. Please try again.']);
} catch (Throwable $e) { snc_inventory_json(503,['ok'=>false,'message'=>'Unable to save inventory. Please try again.']); }
