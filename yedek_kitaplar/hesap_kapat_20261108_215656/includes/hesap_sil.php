<?php
/** Permanently removes a WordNest account and its related records. */
function wnAccountQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function wnAccountPrimaryKey(PDO $db, string $schema, string $table): array
{
    $query = $db->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
        ORDER BY ORDINAL_POSITION");
    $query->execute([$schema, $table]);
    return $query->fetchAll(PDO::FETCH_COLUMN);
}

function wnAccountDeleteRow(PDO $db, string $schema, string $table, array $row, array &$states, int $depth = 0): void
{
    if ($depth > 40) throw new RuntimeException('İlişkili kayıtlar beklenenden fazla iç içe geçmiş.');
    $primaryKey = wnAccountPrimaryKey($db, $schema, $table);
    if (!$primaryKey) throw new RuntimeException('İlişkili tabloda birincil anahtar bulunamadı.');
    $identity = [];
    foreach ($primaryKey as $column) {
        if (!array_key_exists($column, $row)) throw new RuntimeException('İlişkili kayıt güvenli biçimde tanımlanamadı.');
        $identity[$column] = $row[$column];
    }
    $signature = $schema . '.' . $table . ':' . hash('sha256', serialize($identity));
    if (($states[$signature] ?? '') === 'deleted') return;
    if (($states[$signature] ?? '') === 'visiting') throw new RuntimeException('Veritabanında döngüsel kayıt ilişkisi bulundu.');
    $states[$signature] = 'visiting';

    $fkQuery = $db->prepare("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?
        ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    $fkQuery->execute([$schema, $schema, $table]);
    $foreignKeys = [];
    foreach ($fkQuery->fetchAll(PDO::FETCH_ASSOC) as $fk) $foreignKeys[$fk['TABLE_NAME'] . "\0" . $fk['CONSTRAINT_NAME']][] = $fk;
    foreach ($foreignKeys as $columns) {
        $childTable = $columns[0]['TABLE_NAME'];
        $conditions = [];
        $values = [];
        foreach ($columns as $column) {
            $parentColumn = $column['REFERENCED_COLUMN_NAME'];
            if (!array_key_exists($parentColumn, $row) || $row[$parentColumn] === null) { $conditions = []; break; }
            $conditions[] = wnAccountQuoteIdentifier($column['COLUMN_NAME']) . ' = ?';
            $values[] = $row[$parentColumn];
        }
        if (!$conditions) continue;
        $children = $db->prepare('SELECT * FROM ' . wnAccountQuoteIdentifier($childTable) . ' WHERE ' . implode(' AND ', $conditions));
        $children->execute($values);
        foreach ($children->fetchAll(PDO::FETCH_ASSOC) as $childRow) wnAccountDeleteRow($db, $schema, $childTable, $childRow, $states, $depth + 1);
    }
    $where = [];
    $values = [];
    foreach ($identity as $column => $value) { $where[] = wnAccountQuoteIdentifier($column) . ' = ?'; $values[] = $value; }
    $delete = $db->prepare('DELETE FROM ' . wnAccountQuoteIdentifier($table) . ' WHERE ' . implode(' AND ', $where));
    $delete->execute($values);
    $states[$signature] = 'deleted';
}

function wnDeleteWordNestAccount(PDO $db, int $userId): void
{
    $schema = (string) $db->query('SELECT DATABASE()')->fetchColumn();
    if ($schema === '') throw new RuntimeException('WordNest veritabanı seçili değil.');
    $userQuery = $db->prepare('SELECT * FROM `kullanicilar` WHERE `id` = ? LIMIT 1');
    $userQuery->execute([$userId]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new RuntimeException('Hesap bulunamadı.');

    $userColumns = ['kullanici_id','user_id','uye_id','sahip_id','olusturan_id','takip_eden_id','takip_edilen_id','gonderen_id','alici_id','raporlayan_id','sikayet_eden_id','sikayet_edilen_id','hedef_kullanici_id'];
    $marks = implode(',', array_fill(0, count($userColumns), '?'));
    $columns = $db->prepare("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME <> 'kullanicilar' AND COLUMN_NAME IN ($marks)
        ORDER BY TABLE_NAME, ORDINAL_POSITION");
    $columns->execute(array_merge([$schema], $userColumns));
    $states = [];
    foreach ($columns->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $table = $column['TABLE_NAME'];
        $name = $column['COLUMN_NAME'];
        $rows = $db->prepare('SELECT * FROM ' . wnAccountQuoteIdentifier($table) . ' WHERE ' . wnAccountQuoteIdentifier($name) . ' = ?');
        $rows->execute([$userId]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) wnAccountDeleteRow($db, $schema, $table, $row, $states);
    }
    wnAccountDeleteRow($db, $schema, 'kullanicilar', $user, $states);
}
