<?php
declare(strict_types=1);
require_once __DIR__.'/audit.php';

const SNC_INVENTORY_TYPES = ['sale' => 'Sale Deduction', 'opening' => 'Opening stock', 'delivery' => 'Stock Received', 'usage' => 'Stock Used', 'waste' => 'Waste / Spoilage', 'correction_in' => 'Correction — Increase', 'correction_out' => 'Correction — Decrease'];

function snc_inventory_decimal(int $milli): string
{
    return ($milli < 0 ? '-' : '') . intdiv(abs($milli), 1000) . '.' . str_pad((string) (abs($milli) % 1000), 3, '0', STR_PAD_LEFT);
}

function snc_inventory_parse(mixed $value): int
{
    if (!is_string($value) || !preg_match('/^\d{1,7}(?:\.\d{1,3})?$/D', trim($value))) throw new InvalidArgumentException('Enter a quantity from 0 to 9,999,999.999, with up to three decimal places.');
    $parts = explode('.', trim($value));
    return ((int) $parts[0]) * 1000 + (int) str_pad($parts[1] ?? '', 3, '0');
}

function snc_inventory_text(mixed $value, int $length, string $label): string
{
    if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $length || preg_match('/[\x00-\x1f\x7f]/u', $value)) throw new InvalidArgumentException("Enter {$label} between 1 and {$length} characters.");
    return trim($value);
}

function snc_inventory_qty(mixed $value): string
{
    return rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
}

function snc_inventory_mutate(PDO $pdo, array $user, array $data): array
{
    $action = $data['action'] ?? '';
    if (!in_array($action, ['save_ingredient', 'movement'], true)) throw new InvalidArgumentException('Choose a valid inventory action.');
    $id = filter_var($data['id'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($id === false || ($action === 'movement' && !$id)) throw new InvalidArgumentException('Choose a valid ingredient.');
    $requestKey = $data['request_key'] ?? '';
    if (!is_string($requestKey) || !preg_match('/^[a-f0-9]{32}$/D', $requestKey)) throw new InvalidArgumentException('Refresh and reopen the form before saving.');
    // Stable across retries; include every submitted field except the request key.
    $hashData = $data; unset($hashData['request_key']); ksort($hashData);
    $hash = hash('sha256', json_encode($hashData, JSON_THROW_ON_ERROR));
    $owns = !$pdo->inTransaction();
    if ($owns) $pdo->beginTransaction();
    try {
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM inventory_ingredients WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]); $ingredient = $stmt->fetch();
            if (!$ingredient) throw new RuntimeException('Ingredient no longer exists. Refresh and try again.', 409);
        }
        $stmt = $pdo->prepare('SELECT request_hash, actor_id FROM inventory_movements WHERE request_key = ?');
        $stmt->execute([$requestKey]); $existing = $stmt->fetch();
        if ($existing) {
            if (!hash_equals($existing['request_hash'], $hash) || (int) $existing['actor_id'] !== (int) $user['id']) throw new RuntimeException('This request was already saved with different details. Refresh before recording another movement.', 409);
            if ($owns) $pdo->commit();
            return ['ok' => true, 'message' => 'Stock change was already recorded. No duplicate was added.'];
        }
        if ($action === 'save_ingredient') {
            $name = snc_inventory_text($data['name'] ?? null, 120, 'an ingredient name');
            $unit = $id ? $ingredient['base_unit'] : ($data['base_unit'] ?? '');
            if (!in_array($unit, ['g', 'ml', 'pcs'], true)) throw new InvalidArgumentException('Choose grams, milliliters, or pieces.');
            if ($id && isset($data['base_unit']) && $data['base_unit'] !== $unit) throw new InvalidArgumentException('Base units cannot be changed because they define the stock history.');
            $threshold = snc_inventory_parse($data['low_stock_threshold'] ?? null);
            if ($unit === 'pcs' && $threshold % 1000) throw new InvalidArgumentException('Pieces must use whole numbers.');
            if (!in_array($data['is_active'] ?? null, ['0','1',0,1], true)) throw new InvalidArgumentException('Choose an active status.');
            if ($id) {
                if (isset($data['opening_stock'])) throw new InvalidArgumentException('Use Adjust Stock to change stock quantities.');
                $stmt = $pdo->prepare('UPDATE inventory_ingredients SET name = ?, low_stock_threshold = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$name, snc_inventory_decimal($threshold), (int) $data['is_active'], $id]);
                snc_audit_write($pdo,$user,'inventory','updated','ingredient',$id,$name,['name'=>$ingredient['name'],'base_unit'=>$unit,'low_stock_threshold'=>$ingredient['low_stock_threshold'],'is_active'=>(int)$ingredient['is_active']],['name'=>$name,'base_unit'=>$unit,'low_stock_threshold'=>snc_inventory_decimal($threshold),'is_active'=>(int)$data['is_active']]);
                if ($owns) $pdo->commit();
                return ['ok' => true, 'message' => 'Ingredient updated. Stock quantities have not changed.'];
            }
            $amount = snc_inventory_parse($data['opening_stock'] ?? null);
            if ($unit === 'pcs' && $amount % 1000) throw new InvalidArgumentException('Pieces must use whole numbers.');
            $stmt = $pdo->prepare('INSERT INTO inventory_ingredients(name, base_unit, current_stock, low_stock_threshold, is_active) VALUES(?,?,?,?,?)');
            $stmt->execute([$name, $unit, snc_inventory_decimal($amount), snc_inventory_decimal($threshold), (int) $data['is_active']]);
            $id = (int) $pdo->lastInsertId();
            snc_audit_write($pdo,$user,'inventory','created','ingredient',$id,$name,null,['name'=>$name,'base_unit'=>$unit,'low_stock_threshold'=>snc_inventory_decimal($threshold),'is_active'=>(int)$data['is_active']]);
            $type = 'opening'; $before = 0; $after = $amount;
            $note = $data['reason'] ?? '';
            if (!is_string($note)) throw new InvalidArgumentException('Enter a valid opening stock note.');
            $reason = trim($note) !== '' ? snc_inventory_text($note, 240, 'an opening stock note') : 'Opening stock';
            $message = 'Ingredient added and opening stock recorded.';
        } else {
            if (!(int) $ingredient['is_active']) throw new RuntimeException('Activate this ingredient before recording a movement.', 409);
            $name = $ingredient['name']; $unit = $ingredient['base_unit'];
            $type = $data['movement_type'] ?? '';
            if (!in_array($type, ['delivery','usage','waste','correction_in','correction_out'], true)) throw new InvalidArgumentException('Choose a movement type.');
            $entryUnit = $data['entry_unit'] ?? '';
            $allowed = [$unit => 1];
            if ($unit === 'g') $allowed['kg'] = 1000;
            if ($unit === 'ml') $allowed['L'] = 1000;
            if (!is_string($entryUnit) || !isset($allowed[$entryUnit])) throw new InvalidArgumentException('Choose a unit compatible with the ingredient.');
            $amount = snc_inventory_parse($data['quantity'] ?? null) * $allowed[$entryUnit];
            if ($amount <= 0 || $amount > 9999999999) throw new InvalidArgumentException('Enter a positive quantity within the stock limit.');
            if ($unit === 'pcs' && $amount % 1000) throw new InvalidArgumentException('Pieces must use whole numbers.');
            $reason = snc_inventory_text($data['reason'] ?? null, 240, 'a movement reason');
            $before = snc_inventory_parse($ingredient['current_stock']);
            if (in_array($type, ['usage','waste','correction_out'], true)) $amount = -$amount;
            $after = $before + $amount;
            if ($after < 0) throw new RuntimeException('Not enough stock. Refresh to check the current balance, then try again.', 409);
            if ($after > 9999999999) throw new InvalidArgumentException('The resulting stock exceeds 9,999,999.999 base units.');
            $stmt = $pdo->prepare('UPDATE inventory_ingredients SET current_stock = ? WHERE id = ?');
            $stmt->execute([snc_inventory_decimal($after), $id]);
            $message = 'Stock movement recorded.';
        }
        $stmt = $pdo->prepare('INSERT INTO inventory_movements (ingredient_id,ingredient_name,base_unit,movement_type,quantity_delta,balance_before,balance_after,reason,actor_id,actor_name,request_key,request_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$id,$name,$unit,$type,snc_inventory_decimal($amount),snc_inventory_decimal($before),snc_inventory_decimal($after),$reason,(int)$user['id'],(string)$user['full_name'],$requestKey,$hash]);
        if ($owns) $pdo->commit();
        return ['ok' => true, 'message' => $message];
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
