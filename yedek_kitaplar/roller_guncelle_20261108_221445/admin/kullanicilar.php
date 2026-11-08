<?php
session_start();
require_once __DIR__ . "/../config/baglanti.php";

if (!isset($_SESSION["kullanici_id"]) || ($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: ../kullanici/giris.php");
    exit;
}

function wnQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function wnPrimaryKey(PDO $db, string $schema, string $table): array
{
    $query = $db->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
        ORDER BY ORDINAL_POSITION");
    $query->execute([$schema, $table]);
    return $query->fetchAll(PDO::FETCH_COLUMN);
}

/** Deletes a database row and rows that depend on it through real foreign keys. */
function wnDeleteRow(PDO $db, string $schema, string $table, array $row, array &$states, int $depth = 0): void
{
    if ($depth > 40) {
        throw new RuntimeException('İlişkili kayıtlar beklenenden fazla iç içe geçmiş.');
    }

    $primaryKey = wnPrimaryKey($db, $schema, $table);
    if (!$primaryKey) {
        throw new RuntimeException('Bir ilişkili tabloda birincil anahtar bulunamadı.');
    }
    $identity = [];
    foreach ($primaryKey as $column) {
        if (!array_key_exists($column, $row)) {
            throw new RuntimeException('İlişkili kayıt güvenli biçimde tanımlanamadı.');
        }
        $identity[$column] = $row[$column];
    }
    $signature = $schema . '.' . $table . ':' . hash('sha256', serialize($identity));
    if (($states[$signature] ?? '') === 'deleted') return;
    if (($states[$signature] ?? '') === 'visiting') {
        throw new RuntimeException('Veritabanında döngüsel kayıt ilişkisi bulundu.');
    }
    $states[$signature] = 'visiting';

    $fkQuery = $db->prepare("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?
        ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    $fkQuery->execute([$schema, $schema, $table]);
    $foreignKeys = [];
    foreach ($fkQuery->fetchAll(PDO::FETCH_ASSOC) as $fk) {
        $foreignKeys[$fk['TABLE_NAME'] . "\0" . $fk['CONSTRAINT_NAME']][] = $fk;
    }

    foreach ($foreignKeys as $columns) {
        $childTable = $columns[0]['TABLE_NAME'];
        $conditions = [];
        $values = [];
        foreach ($columns as $column) {
            $parentColumn = $column['REFERENCED_COLUMN_NAME'];
            if (!array_key_exists($parentColumn, $row) || $row[$parentColumn] === null) {
                $conditions = [];
                break;
            }
            $conditions[] = wnQuoteIdentifier($column['COLUMN_NAME']) . ' = ?';
            $values[] = $row[$parentColumn];
        }
        if (!$conditions) continue;

        $sql = 'SELECT * FROM ' . wnQuoteIdentifier($childTable) . ' WHERE ' . implode(' AND ', $conditions);
        $children = $db->prepare($sql);
        $children->execute($values);
        foreach ($children->fetchAll(PDO::FETCH_ASSOC) as $childRow) {
            wnDeleteRow($db, $schema, $childTable, $childRow, $states, $depth + 1);
        }
    }

    $where = [];
    $values = [];
    foreach ($identity as $column => $value) {
        $where[] = wnQuoteIdentifier($column) . ' = ?';
        $values[] = $value;
    }
    $delete = $db->prepare('DELETE FROM ' . wnQuoteIdentifier($table) . ' WHERE ' . implode(' AND ', $where));
    $delete->execute($values);
    $states[$signature] = 'deleted';
}

/** Also catches app tables that store a user ID without declaring a foreign key. */
function wnDeleteRowsOwnedByUser(PDO $db, string $schema, int $userId, array &$states): void
{
    $userColumns = [
        'kullanici_id', 'user_id', 'uye_id', 'sahip_id', 'olusturan_id',
        'takip_eden_id', 'takip_edilen_id', 'gonderen_id', 'alici_id',
        'raporlayan_id', 'sikayet_eden_id', 'sikayet_edilen_id', 'hedef_kullanici_id'
    ];
    $marks = implode(',', array_fill(0, count($userColumns), '?'));
    $query = $db->prepare("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME <> 'kullanicilar' AND COLUMN_NAME IN ($marks)
        ORDER BY TABLE_NAME, ORDINAL_POSITION");
    $query->execute(array_merge([$schema], $userColumns));

    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $table = $column['TABLE_NAME'];
        $name = $column['COLUMN_NAME'];
        $rowsQuery = $db->prepare('SELECT * FROM ' . wnQuoteIdentifier($table) . ' WHERE ' . wnQuoteIdentifier($name) . ' = ?');
        $rowsQuery->execute([$userId]);
        foreach ($rowsQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
            wnDeleteRow($db, $schema, $table, $row, $states);
        }
    }
}

if (empty($_SESSION['wn_kullanici_csrf'])) {
    $_SESSION['wn_kullanici_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['wn_kullanici_csrf'];
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $postedCsrf = (string) ($_POST['csrf'] ?? '');
    $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if (!hash_equals($csrf, $postedCsrf)) {
        $message = 'İstek doğrulanamadı. Sayfayı yenileyip tekrar dene.';
        $messageType = 'error';
    } elseif (!$targetId || $targetId === (int) $_SESSION['kullanici_id']) {
        $message = 'Kendi hesabını bu ekrandan silemezsin.';
        $messageType = 'error';
    } else {
        try {
            $userQuery = $db->prepare('SELECT * FROM kullanicilar WHERE id = ? LIMIT 1');
            $userQuery->execute([$targetId]);
            $targetUser = $userQuery->fetch(PDO::FETCH_ASSOC);
            if (!$targetUser) {
                throw new RuntimeException('Kullanıcı bulunamadı.');
            }
            $protectedRoles = ['admin', 'root_admin', 'moderator'];
            if (in_array(strtolower((string) ($targetUser['rol'] ?? '')), $protectedRoles, true)) {
                throw new RuntimeException('Yönetici ve moderatör hesapları bu ekrandan silinemez.');
            }

            $schema = (string) $db->query('SELECT DATABASE()')->fetchColumn();
            if ($schema === '') throw new RuntimeException('WordNest veritabanı seçili değil.');
            $db->beginTransaction();
            $states = [];
            wnDeleteRowsOwnedByUser($db, $schema, (int) $targetId, $states);
            // Recursively remove any remaining records linked by database foreign keys.
            wnDeleteRow($db, $schema, 'kullanicilar', $targetUser, $states);
            $db->commit();
            header('Location: kullanicilar.php?deleted=1');
            exit;
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('WordNest user deletion failed: ' . $error->getMessage());
            $message = $error instanceof RuntimeException
                ? $error->getMessage()
                : 'Silme tamamlanamadı. İşlem geri alındı ve veriler korunuyor.';
            $messageType = 'error';
        }
    }
}

if (isset($_GET['deleted'])) {
    $message = 'Kullanıcı ve ona bağlı kayıtlar silindi.';
    $messageType = 'success';
}

$search = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT id, ad_soyad, kullanici_adi, email, rol, durum, kayit_tarihi FROM kullanicilar';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE kullanici_adi LIKE ? OR email LIKE ? OR ad_soyad LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY id DESC LIMIT 300';
$listQuery = $db->prepare($sql);
$listQuery->execute($params);
$users = $listQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - WordNest</title>
    <link rel="stylesheet" href="../assets/css/gece-modu.css">
    <link rel="stylesheet" href="../assets/css/dil-degistir.css">
    <script>try{if(localStorage.getItem('wordnest-tema')==='gece')document.documentElement.setAttribute('data-wordnest-theme','gece');if(localStorage.getItem('wordnest-dil')==='en'){document.documentElement.lang='en';document.documentElement.setAttribute('data-wordnest-language','en')}}catch(e){}</script>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#fffaf5;color:#302820;font-family:Arial,sans-serif}header{background:#ff6b00;color:#fff;padding:19px 5%;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}header h1{font-size:23px;margin:0}.head-links{display:flex;gap:9px;flex-wrap:wrap}.head-links a{padding:10px 13px;border-radius:8px;background:#fff;color:#a84612;text-decoration:none;font-weight:bold}.wrap{max-width:1180px;width:92%;margin:32px auto 60px}.intro h2{color:#e85d00;margin:0 0 8px}.intro p{color:#71665d;line-height:1.6;margin:0 0 22px}.notice{padding:13px 16px;border-radius:9px;margin:0 0 18px;font-weight:700}.notice.info{background:#fff0df;color:#76501f}.notice.success{background:#e8f7ed;color:#216e38}.notice.error{background:#ffe9e9;color:#a12e2e}.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;background:#fff;border:1px solid #f1e3d8;border-radius:12px;padding:15px;margin-bottom:16px}.toolbar form{display:flex;gap:8px;flex-wrap:wrap}.toolbar input{min-width:250px;padding:10px 12px;border:1px solid #ddcfc4;border-radius:8px;font:inherit}.toolbar button{border:0;border-radius:8px;background:#e85d00;color:#fff;padding:10px 15px;font-weight:bold;cursor:pointer}.table-wrap{overflow-x:auto;background:#fff;border:1px solid #f1e3d8;border-radius:12px}.users{width:100%;border-collapse:collapse;min-width:780px}.users th,.users td{text-align:left;padding:13px 14px;border-bottom:1px solid #f0e8e1}.users th{background:#fff3e9;color:#9b460e;font-size:13px}.users td{font-size:14px}.users tr:last-child td{border-bottom:0}.role{display:inline-block;padding:5px 9px;border-radius:16px;background:#f2eee9;color:#51463d;font-size:12px;font-weight:bold}.delete-btn{border:0;background:#bd2929;color:#fff;border-radius:7px;padding:9px 11px;font-weight:bold;cursor:pointer}.delete-btn:hover{background:#951d1d}.protected{color:#786f67;font-size:12px}.empty{text-align:center;padding:35px;color:#766d65}.count{color:#75685d;font-size:14px}@media(max-width:600px){header{align-items:stretch;flex-direction:column}header h1{text-align:center}.head-links{flex-direction:column}.head-links a{text-align:center}.wrap{margin-top:24px}.toolbar input{min-width:0;width:100%}.toolbar form{width:100%}.toolbar form button{width:100%}}
        html[data-wordnest-theme="gece"] body{background:#171615!important;color:#f1ede8!important}html[data-wordnest-theme="gece"] header{background:#211f1d!important}html[data-wordnest-theme="gece"] .toolbar,html[data-wordnest-theme="gece"] .table-wrap{background:#262321!important;border-color:#45403b!important}html[data-wordnest-theme="gece"] .users th{background:#302a25!important;color:#ffbd8c!important}html[data-wordnest-theme="gece"] .users td,html[data-wordnest-theme="gece"] .intro p,html[data-wordnest-theme="gece"] .count,html[data-wordnest-theme="gece"] .protected{color:#f1ede8!important;border-color:#45403b!important}html[data-wordnest-theme="gece"] .head-links a{background:#302a25!important;color:#fff1e5!important}html[data-wordnest-theme="gece"] .toolbar input{background:#1b1917!important;color:#f1ede8!important;border-color:#514a44!important}
    </style>
</head>
<body>
<header>
    <h1>👥 Kullanıcı Yönetimi</h1>
    <nav class="head-links"><a href="panel.php">⚙️ Yönetim Paneline Dön</a><a href="../index.php">🏠 Ana Sayfa</a></nav>
</header>
<main class="wrap">
    <section class="intro"><h2>Kayıtlı Kullanıcılar</h2><p>Bir kullanıcıyı silersen hesabı, hikâyeleri, bölümleri ve ona bağlı kayıtlar kalıcı olarak kaldırılır.</p></section>
    <?php if ($message !== ''): ?><div class="notice <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <section class="toolbar">
        <strong class="count">Toplam gösterilen: <?= count($users) ?></strong>
        <form method="get" action="kullanicilar.php"><input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ad, kullanıcı adı veya e-posta ara"><button type="submit">Ara</button></form>
    </section>
    <div class="table-wrap"><table class="users">
        <thead><tr><th>Ad Soyad</th><th>Kullanıcı Adı</th><th>E-posta</th><th>Rol</th><th>Kayıt Tarihi</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php if (!$users): ?><tr><td colspan="6" class="empty">Kullanıcı bulunamadı.</td></tr><?php endif; ?>
        <?php foreach ($users as $user):
            $protected = (int) $user['id'] === (int) $_SESSION['kullanici_id'] || in_array(strtolower((string) $user['rol']), ['admin', 'root_admin', 'moderator'], true);
        ?>
            <tr>
                <td><?= htmlspecialchars((string) ($user['ad_soyad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($user['kullanici_adi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="role"><?= htmlspecialchars((string) ($user['rol'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars((string) ($user['kayit_tarihi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if ($protected): ?><span class="protected">Korunan hesap</span><?php else: ?>
                        <form method="post" onsubmit="return confirm('Bu kullanıcının hesabı, hikâyeleri, bölümleri ve bağlantılı tüm kayıtları kalıcı olarak silinecek. Emin misin?')">
                            <input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="delete-btn" type="submit">🗑️ Kullanıcıyı Sil</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</main>
<script src="../assets/js/gece-modu.js"></script>
<script src="../assets/js/dil-degistir.js"></script>
</body>
</html>
