<?php
// ============================================================
// api.php — REST API JSON untuk Klinik Pratama
// ============================================================
// Endpoint:  /api.php?resource=<tabel>&action=<aksi>
//
// Resource yang tersedia:
//   layanan, dokter, jadwal, pasien, pendaftaran,
//   rekam_medis, obat, billing, pesan, admin_users
//
// Action:
//   GET    list        → ambil semua data
//   GET    get&id=X    → ambil satu data
//   POST   create      → tambah data (body JSON)
//   POST   update&id=X → ubah data (body JSON)
//   POST   delete&id=X → hapus data
//   GET    dashboard   → statistik dashboard (resource=dashboard)
// ============================================================

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Helper ─────────────────────────────────────────────────
function json_out(bool $ok, $data = null, string $msg = ''): void {
    echo json_encode(['ok' => $ok, 'data' => $data, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function genId(string $prefix = ''): string {
    return $prefix . strtolower(substr(md5(uniqid('', true)), 0, 8));
}

// ── Router ─────────────────────────────────────────────────
$resource = $_GET['resource'] ?? '';
$action   = $_GET['action']   ?? 'list';
$id       = $_GET['id']       ?? null;

try {
    $pdo = getDB();

    // ─── DASHBOARD ───────────────────────────────────────
    if ($resource === 'dashboard') {
        $today = date('Y-m-d');

        $stats = [];

        // Jumlah pasien
        $stats['total_pasien'] = (int)$pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();

        // Pendaftaran hari ini
        $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE tanggal = ?");
        $st->execute([$today]);
        $stats['pendaftaran_hari_ini'] = (int)$st->fetchColumn();

        // Pendaftaran per status hari ini
        $st = $pdo->prepare("SELECT status, COUNT(*) AS jml FROM pendaftaran WHERE tanggal = ? GROUP BY status");
        $st->execute([$today]);
        $stats['status_pendaftaran'] = $st->fetchAll();

        // Pendapatan hari ini (lunas)
        $st = $pdo->prepare("SELECT COALESCE(SUM(total_biaya),0) FROM billing WHERE tanggal = ? AND status='Lunas'");
        $st->execute([$today]);
        $stats['pendapatan_hari_ini'] = (int)$st->fetchColumn();

        // Total pendapatan lunas
        $stats['total_pendapatan'] = (int)$pdo->query("SELECT COALESCE(SUM(total_biaya),0) FROM billing WHERE status='Lunas'")->fetchColumn();

        // Billing belum lunas
        $stats['tagihan_belum_lunas'] = (int)$pdo->query("SELECT COUNT(*) FROM billing WHERE status='Belum Lunas'")->fetchColumn();

        // Stok obat habis
        $stats['obat_habis'] = (int)$pdo->query("SELECT COUNT(*) FROM obat WHERE status='Habis'")->fetchColumn();

        // Total dokter aktif
        $stats['total_dokter'] = (int)$pdo->query("SELECT COUNT(*) FROM dokter WHERE status='Aktif'")->fetchColumn();

        json_out(true, $stats);
    }

    // ─── TABEL YANG DIIZINKAN ────────────────────────────
    $allowed = ['layanan','dokter','jadwal','pasien','pendaftaran','rekam_medis','obat','billing','pesan','admin_users'];
    if (!in_array($resource, $allowed)) {
        json_out(false, null, 'Resource tidak valid.');
    }

    // ─── LIST ────────────────────────────────────────────
    if ($action === 'list') {
        $search  = $_GET['q'] ?? '';
        $limit   = min((int)($_GET['limit'] ?? 100), 500);
        $offset  = (int)($_GET['offset'] ?? 0);

        // Kolom pencarian per resource
        $searchCols = [
            'layanan'       => ['nama','deskripsi'],
            'dokter'        => ['nama','spesialisasi','email'],
            'jadwal'        => ['dokter_nama','hari'],
            'pasien'        => ['nama','nik','telepon','email'],
            'pendaftaran'   => ['no_reg','pasien_nama','dokter_nama','layanan_nama'],
            'rekam_medis'   => ['pasien_nama','dokter_nama','diagnosis'],
            'obat'          => ['nama','kategori'],
            'billing'       => ['no_bill','pasien_nama','dokter_nama'],
            'pesan'         => ['nama','email','subjek'],
            'admin_users'   => ['nama','username','role'],
        ];

        $where = '';
        $params = [];
        if ($search && isset($searchCols[$resource])) {
            $parts = array_map(fn($c) => "$c LIKE ?", $searchCols[$resource]);
            $where = 'WHERE ' . implode(' OR ', $parts);
            $params = array_fill(0, count($searchCols[$resource]), "%$search%");
        }

        // Filter tambahan
        if ($resource === 'pendaftaran' && !empty($_GET['tanggal'])) {
            $where = $where ? "$where AND tanggal = ?" : "WHERE tanggal = ?";
            $params[] = $_GET['tanggal'];
        }
        if ($resource === 'billing' && !empty($_GET['status'])) {
            $where = $where ? "$where AND status = ?" : "WHERE status = ?";
            $params[] = $_GET['status'];
        }

        $sql = "SELECT * FROM `$resource` $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        // rekam_medis dan jadwal tidak punya created_at index, fallback ke id
        if (in_array($resource, ['jadwal'])) {
            $sql = "SELECT * FROM `$resource` $where LIMIT $limit OFFSET $offset";
        }

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        // Decode JSON kolom hak_akses
        if ($resource === 'admin_users') {
            foreach ($rows as &$r) {
                $r['hak_akses'] = json_decode($r['hak_akses'] ?? '[]', true);
                unset($r['password']); // jangan expose password
            }
        }

        // Sertakan billing_item jika resource billing
        if ($resource === 'billing') {
            $ids = array_column($rows, 'id');
            if ($ids) {
                $in  = implode(',', array_fill(0, count($ids), '?'));
                $stItem = $pdo->prepare("SELECT * FROM billing_item WHERE billing_id IN ($in)");
                $stItem->execute($ids);
                $items = $stItem->fetchAll();
                $grouped = [];
                foreach ($items as $it) $grouped[$it['billing_id']][] = $it;
                foreach ($rows as &$r) $r['item_list'] = $grouped[$r['id']] ?? [];
            }
        }

        json_out(true, $rows);
    }

    // ─── GET SINGLE ──────────────────────────────────────
    if ($action === 'get') {
        if (!$id) json_out(false, null, 'ID tidak boleh kosong.');
        $st = $pdo->prepare("SELECT * FROM `$resource` WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) json_out(false, null, 'Data tidak ditemukan.');
        if ($resource === 'admin_users') {
            $row['hak_akses'] = json_decode($row['hak_akses'] ?? '[]', true);
            unset($row['password']);
        }
        if ($resource === 'billing') {
            $stItem = $pdo->prepare("SELECT * FROM billing_item WHERE billing_id = ?");
            $stItem->execute([$id]);
            $row['item_list'] = $stItem->fetchAll();
        }
        json_out(true, $row);
    }

    // ─── CREATE ──────────────────────────────────────────
    if ($action === 'create') {
        $data = body();
        if (empty($data)) json_out(false, null, 'Body kosong.');

        if (!isset($data['id'])) $data['id'] = genId($resource[0]);

        // Validasi wajib per resource
        $required = [
            'pasien'      => ['nama','nik','ttl','jk'],
            'dokter'      => ['nama','spesialisasi'],
            'pendaftaran' => ['pasien_id','tanggal'],
            'rekam_medis' => ['pasien_id','tanggal','diagnosis'],
            'obat'        => ['nama','harga'],
            'billing'     => ['pasien_id','tanggal','total_biaya'],
            'admin_users' => ['nama','username','password','role'],
        ];
        foreach (($required[$resource] ?? []) as $req) {
            if (empty($data[$req])) json_out(false, null, "Field '$req' wajib diisi.");
        }

        // Encode JSON field
        if ($resource === 'admin_users' && isset($data['hak_akses'])) {
            $data['hak_akses'] = json_encode($data['hak_akses']);
        }

        $cols = array_keys($data);
        $sql  = "INSERT INTO `$resource` (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        $pdo->prepare($sql)->execute(array_values($data));

        // Simpan billing_item jika ada
        if ($resource === 'billing' && !empty($data['item_list'])) {
            $stItem = $pdo->prepare("INSERT INTO billing_item (billing_id,nama,jumlah,harga) VALUES (?,?,?,?)");
            foreach ($data['item_list'] as $it) {
                $stItem->execute([$data['id'], $it['nama'], $it['jumlah'], $it['harga']]);
            }
        }

        json_out(true, ['id' => $data['id']], 'Data berhasil ditambahkan.');
    }

    // ─── UPDATE ──────────────────────────────────────────
    if ($action === 'update') {
        if (!$id) json_out(false, null, 'ID tidak boleh kosong.');
        $data = body();
        if (empty($data)) json_out(false, null, 'Body kosong.');

        unset($data['id']); // ID tidak boleh diubah

        if ($resource === 'admin_users' && isset($data['hak_akses'])) {
            $data['hak_akses'] = json_encode($data['hak_akses']);
        }
        // Hapus password kosong agar tidak menimpa yang lama
        if ($resource === 'admin_users' && empty($data['password'])) {
            unset($data['password']);
        }

        $set  = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $pdo->prepare("UPDATE `$resource` SET $set WHERE id = ?")->execute($vals);

        // Update billing_item
        if ($resource === 'billing' && isset($data['item_list'])) {
            $pdo->prepare("DELETE FROM billing_item WHERE billing_id = ?")->execute([$id]);
            $stItem = $pdo->prepare("INSERT INTO billing_item (billing_id,nama,jumlah,harga) VALUES (?,?,?,?)");
            foreach ($data['item_list'] as $it) {
                $stItem->execute([$id, $it['nama'], $it['jumlah'], $it['harga']]);
            }
        }

        json_out(true, ['id' => $id], 'Data berhasil diperbarui.');
    }

    // ─── DELETE ──────────────────────────────────────────
    if ($action === 'delete') {
        if (!$id) json_out(false, null, 'ID tidak boleh kosong.');
        $pdo->prepare("DELETE FROM `$resource` WHERE id = ?")->execute([$id]);
        json_out(true, ['id' => $id], 'Data berhasil dihapus.');
    }

    // ─── LOGIN ───────────────────────────────────────────
    if ($resource === 'auth' && $action === 'login') {
        $data = body();
        $st   = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'Aktif'");
        $st->execute([$data['username'] ?? '']);
        $user = $st->fetch();

        // Cek password (plaintext demo; pakai password_verify() jika bcrypt)
        if ($user && $user['password'] === ($data['password'] ?? '')) {
            $user['hak_akses'] = json_decode($user['hak_akses'], true);
            unset($user['password']);
            json_out(true, $user, 'Login berhasil.');
        }
        json_out(false, null, 'Username atau password salah.');
    }

    json_out(false, null, 'Action tidak dikenal.');

} catch (PDOException $e) {
    http_response_code(500);
    json_out(false, null, 'Database error: ' . $e->getMessage());
} catch (Throwable $e) {
    http_response_code(500);
    json_out(false, null, 'Server error: ' . $e->getMessage());
}
