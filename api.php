<?php
// ============================================================
// api.php — REST API Backend untuk Klinik Pratama
// ============================================================
// Endpoint: api.php?store=<nama_store>&action=<aksi>
//
// Store yang tersedia (sama persis dengan IndexedDB di app):
//   pasien, dokter, layanan, pendaftaran, jadwal,
//   rekammedis, obat, pesan, billing, adminusers
//
// Action:
//   getAll          → ambil semua data (GET)
//   put             → tambah / update data (POST, body JSON berisi objek)
//   delete          → hapus data (POST, body: {"id":"..."})
//   clear           → hapus semua data di store (POST)
//   login           → cek login admin (POST, body: {"username","password"})
//   loginPasien     → cek login pasien by NIK+TTL (POST)
//   dashboard       → statistik dashboard (GET)
// ============================================================

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Helpers ────────────────────────────────────────────────
function out(bool $ok, $data = null, string $msg = ''): void {
    echo json_encode(['ok' => $ok, 'data' => $data, 'msg' => $msg], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

// Nama tabel di MySQL (snake_case) dari nama store JS (camelCase)
function tableOf(string $store): string {
    // rekammedis → rekammedis (sudah sama), adminusers → adminusers
    // semua store sudah lowercase di SQL, tinggal return langsung
    $map = [
        'pasien'       => 'pasien',
        'dokter'       => 'dokter',
        'layanan'      => 'layanan',
        'pendaftaran'  => 'pendaftaran',
        'jadwal'       => 'jadwal',
        'rekammedis'   => 'rekammedis',
        'obat'         => 'obat',
        'pesan'        => 'pesan',
        'billing'      => 'billing',
        'adminusers'   => 'adminusers',
    ];
    return $map[$store] ?? '';
}

// Konversi row MySQL (snake_case) ke format JS (camelCase) sesuai app
function toJS(string $store, array $row): array {
    switch ($store) {
        case 'dokter':
            if (isset($row['layanan_id'])) { $row['layananId'] = $row['layanan_id']; unset($row['layanan_id']); }
            break;
        case 'jadwal':
            if (isset($row['dokter_id']))   { $row['dokterId']   = $row['dokter_id'];   unset($row['dokter_id']); }
            if (isset($row['dokter_nama'])) { $row['dokterNama'] = $row['dokter_nama']; unset($row['dokter_nama']); }
            if (isset($row['jam_mulai']))   { $row['jamMulai']   = $row['jam_mulai'];   unset($row['jam_mulai']); }
            if (isset($row['jam_selesai'])) { $row['jamSelesai'] = $row['jam_selesai']; unset($row['jam_selesai']); }
            break;
        case 'pendaftaran':
            if (isset($row['no_reg']))      { $row['noReg']      = $row['no_reg'];      unset($row['no_reg']); }
            if (isset($row['pasien_id']))   { $row['pasienId']   = $row['pasien_id'];   unset($row['pasien_id']); }
            if (isset($row['pasien_nama'])) { $row['pasienNama'] = $row['pasien_nama']; unset($row['pasien_nama']); }
            if (isset($row['layanan_id']))  { $row['layananId']  = $row['layanan_id'];  unset($row['layanan_id']); }
            if (isset($row['layanan_nama'])){ $row['layananNama']= $row['layanan_nama'];unset($row['layanan_nama']); }
            if (isset($row['dokter_id']))   { $row['dokterId']   = $row['dokter_id'];   unset($row['dokter_id']); }
            if (isset($row['dokter_nama'])) { $row['dokterNama'] = $row['dokter_nama']; unset($row['dokter_nama']); }
            if (isset($row['created_at']))  { $row['createdAt']  = $row['created_at'];  unset($row['created_at']); }
            break;
        case 'rekammedis':
            if (isset($row['pasien_id']))    { $row['pasienId']    = $row['pasien_id'];    unset($row['pasien_id']); }
            if (isset($row['pasien_nama']))  { $row['pasienNama']  = $row['pasien_nama'];  unset($row['pasien_nama']); }
            if (isset($row['dokter_id']))    { $row['dokterId']    = $row['dokter_id'];    unset($row['dokter_id']); }
            if (isset($row['dokter_nama']))  { $row['dokterNama']  = $row['dokter_nama'];  unset($row['dokter_nama']); }
            if (isset($row['tekanan_darah'])){ $row['tekananDarah']= $row['tekanan_darah'];unset($row['tekanan_darah']); }
            if (isset($row['obat_id']))      { $row['obatId']      = $row['obat_id'];      unset($row['obat_id']); }
            if (isset($row['obat_nama']))    { $row['obatNama']    = $row['obat_nama'];    unset($row['obat_nama']); }
            if (isset($row['created_at']))   { $row['createdAt']   = $row['created_at'];   unset($row['created_at']); }
            break;
        case 'billing':
            if (isset($row['no_bill']))           { $row['noBill']           = $row['no_bill'];           unset($row['no_bill']); }
            if (isset($row['pasien_id']))         { $row['pasienId']         = $row['pasien_id'];         unset($row['pasien_id']); }
            if (isset($row['pasien_nama']))       { $row['pasienNama']       = $row['pasien_nama'];       unset($row['pasien_nama']); }
            if (isset($row['pendaftaran_id']))    { $row['pendaftaranId']    = $row['pendaftaran_id'];    unset($row['pendaftaran_id']); }
            if (isset($row['no_reg']))            { $row['noReg']            = $row['no_reg'];            unset($row['no_reg']); }
            if (isset($row['layanan_nama']))      { $row['layananNama']      = $row['layanan_nama'];      unset($row['layanan_nama']); }
            if (isset($row['dokter_nama']))       { $row['dokterNama']       = $row['dokter_nama'];       unset($row['dokter_nama']); }
            if (isset($row['item_list']))         {
                $row['itemList'] = is_string($row['item_list']) ? json_decode($row['item_list'], true) : $row['item_list'];
                unset($row['item_list']);
            }
            if (isset($row['total_biaya']))       { $row['totalBiaya']       = (int)$row['total_biaya'];  unset($row['total_biaya']); }
            if (isset($row['metode_pembayaran'])) { $row['metodePembayaran'] = $row['metode_pembayaran']; unset($row['metode_pembayaran']); }
            if (isset($row['tanggal_bayar']))     { $row['tanggalBayar']     = $row['tanggal_bayar'];     unset($row['tanggal_bayar']); }
            if (isset($row['created_at']))        { $row['createdAt']        = $row['created_at'];        unset($row['created_at']); }
            break;
        case 'pesan':
            $row['dibaca'] = (bool)($row['dibaca'] ?? false);
            if (isset($row['created_at'])) { $row['createdAt'] = $row['created_at']; unset($row['created_at']); }
            break;
        case 'adminusers':
            if (isset($row['hak_akses'])) {
                $row['hakAkses'] = is_string($row['hak_akses']) ? json_decode($row['hak_akses'], true) : $row['hak_akses'];
                unset($row['hak_akses']);
            }
            if (isset($row['created_at'])) { $row['createdAt'] = $row['created_at']; unset($row['created_at']); }
            break;
    }
    return $row;
}

// Konversi objek JS (camelCase) ke kolom MySQL (snake_case)
function toSQL(string $store, array $obj): array {
    switch ($store) {
        case 'dokter':
            if (array_key_exists('layananId', $obj)) { $obj['layanan_id'] = $obj['layananId']; unset($obj['layananId']); }
            break;
        case 'jadwal':
            if (array_key_exists('dokterId',   $obj)) { $obj['dokter_id']   = $obj['dokterId'];   unset($obj['dokterId']); }
            if (array_key_exists('dokterNama', $obj)) { $obj['dokter_nama'] = $obj['dokterNama']; unset($obj['dokterNama']); }
            if (array_key_exists('jamMulai',   $obj)) { $obj['jam_mulai']   = $obj['jamMulai'];   unset($obj['jamMulai']); }
            if (array_key_exists('jamSelesai', $obj)) { $obj['jam_selesai'] = $obj['jamSelesai']; unset($obj['jamSelesai']); }
            break;
        case 'pendaftaran':
            if (array_key_exists('noReg',      $obj)) { $obj['no_reg']      = $obj['noReg'];      unset($obj['noReg']); }
            if (array_key_exists('pasienId',   $obj)) { $obj['pasien_id']   = $obj['pasienId'];   unset($obj['pasienId']); }
            if (array_key_exists('pasienNama', $obj)) { $obj['pasien_nama'] = $obj['pasienNama']; unset($obj['pasienNama']); }
            if (array_key_exists('layananId',  $obj)) { $obj['layanan_id']  = $obj['layananId'];  unset($obj['layananId']); }
            if (array_key_exists('layananNama',$obj)) { $obj['layanan_nama']= $obj['layananNama'];unset($obj['layananNama']); }
            if (array_key_exists('dokterId',   $obj)) { $obj['dokter_id']   = $obj['dokterId'];   unset($obj['dokterId']); }
            if (array_key_exists('dokterNama', $obj)) { $obj['dokter_nama'] = $obj['dokterNama']; unset($obj['dokterNama']); }
            if (array_key_exists('createdAt',  $obj)) { $obj['created_at']  = $obj['createdAt'];  unset($obj['createdAt']); }
            break;
        case 'rekammedis':
            if (array_key_exists('pasienId',    $obj)) { $obj['pasien_id']    = $obj['pasienId'];    unset($obj['pasienId']); }
            if (array_key_exists('pasienNama',  $obj)) { $obj['pasien_nama']  = $obj['pasienNama'];  unset($obj['pasienNama']); }
            if (array_key_exists('dokterId',    $obj)) { $obj['dokter_id']    = $obj['dokterId'];    unset($obj['dokterId']); }
            if (array_key_exists('dokterNama',  $obj)) { $obj['dokter_nama']  = $obj['dokterNama'];  unset($obj['dokterNama']); }
            if (array_key_exists('tekananDarah',$obj)) { $obj['tekanan_darah']= $obj['tekananDarah'];unset($obj['tekananDarah']); }
            if (array_key_exists('obatId',      $obj)) { $obj['obat_id']      = $obj['obatId'];      unset($obj['obatId']); }
            if (array_key_exists('obatNama',    $obj)) { $obj['obat_nama']    = $obj['obatNama'];    unset($obj['obatNama']); }
            if (array_key_exists('createdAt',   $obj)) { $obj['created_at']   = $obj['createdAt'];   unset($obj['createdAt']); }
            break;
        case 'billing':
            if (array_key_exists('noBill',           $obj)) { $obj['no_bill']           = $obj['noBill'];           unset($obj['noBill']); }
            if (array_key_exists('pasienId',         $obj)) { $obj['pasien_id']         = $obj['pasienId'];         unset($obj['pasienId']); }
            if (array_key_exists('pasienNama',       $obj)) { $obj['pasien_nama']       = $obj['pasienNama'];       unset($obj['pasienNama']); }
            if (array_key_exists('pendaftaranId',    $obj)) { $obj['pendaftaran_id']    = $obj['pendaftaranId'];    unset($obj['pendaftaranId']); }
            if (array_key_exists('noReg',            $obj)) { $obj['no_reg']            = $obj['noReg'];            unset($obj['noReg']); }
            if (array_key_exists('layananNama',      $obj)) { $obj['layanan_nama']      = $obj['layananNama'];      unset($obj['layananNama']); }
            if (array_key_exists('dokterNama',       $obj)) { $obj['dokter_nama']       = $obj['dokterNama'];       unset($obj['dokterNama']); }
            if (array_key_exists('itemList',         $obj)) { $obj['item_list']         = json_encode($obj['itemList']); unset($obj['itemList']); }
            if (array_key_exists('totalBiaya',       $obj)) { $obj['total_biaya']       = $obj['totalBiaya'];       unset($obj['totalBiaya']); }
            if (array_key_exists('metodePembayaran', $obj)) { $obj['metode_pembayaran'] = $obj['metodePembayaran']; unset($obj['metodePembayaran']); }
            if (array_key_exists('tanggalBayar',     $obj)) { $obj['tanggal_bayar']     = $obj['tanggalBayar'];     unset($obj['tanggalBayar']); }
            if (array_key_exists('createdAt',        $obj)) { $obj['created_at']        = $obj['createdAt'];        unset($obj['createdAt']); }
            break;
        case 'pesan':
            $obj['dibaca'] = empty($obj['dibaca']) ? 0 : 1;
            if (array_key_exists('createdAt', $obj)) { $obj['created_at'] = $obj['createdAt']; unset($obj['createdAt']); }
            break;
        case 'adminusers':
            if (array_key_exists('hakAkses', $obj)) { $obj['hak_akses'] = json_encode($obj['hakAkses']); unset($obj['hakAkses']); }
            if (array_key_exists('createdAt',$obj)) { $obj['created_at'] = $obj['createdAt']; unset($obj['createdAt']); }
            break;
    }
    return $obj;
}

// ── Main Router ────────────────────────────────────────────
$store  = $_GET['store']  ?? '';
$action = $_GET['action'] ?? 'getAll';

try {
    $pdo   = getDB();
    $table = tableOf($store);

    // ── DASHBOARD ─────────────────────────────────────────
    if ($store === 'dashboard') {
        $today = date('Y-m-d');
        $d = [];

        $st = $pdo->prepare("SELECT COUNT(*) FROM pasien"); $st->execute(); $d['totalPasien'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM dokter WHERE status='Aktif'"); $st->execute(); $d['totalDokter'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE tanggal=?"); $st->execute([$today]); $d['pendaftaranHariIni'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE status='Menunggu'"); $st->execute(); $d['antrian'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(total_biaya),0) FROM billing WHERE status='Lunas' AND tanggal=?"); $st->execute([$today]); $d['pendapatanHariIni'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(total_biaya),0) FROM billing WHERE status='Lunas'"); $st->execute(); $d['totalPendapatan'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM billing WHERE status='Belum Lunas'"); $st->execute(); $d['belumLunas'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM obat WHERE status='Habis'"); $st->execute(); $d['obatHabis'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM pesan WHERE dibaca=0"); $st->execute(); $d['pesanBelumDibaca'] = (int)$st->fetchColumn();

        out(true, $d);
    }

    // Validasi store
    if (!$table) out(false, null, 'Store tidak valid: ' . $store);

    // ── GET ALL ───────────────────────────────────────────
    if ($action === 'getAll') {
        $st = $pdo->prepare("SELECT * FROM `$table` ORDER BY created_at DESC");
        $st->execute();
        $rows = $st->fetchAll();
        $result = array_map(fn($r) => toJS($store, $r), $rows);
        out(true, $result);
    }

    // ── PUT (insert or update) ────────────────────────────
    if ($action === 'put') {
        $obj = body();
        if (empty($obj) || !isset($obj['id'])) out(false, null, 'Data tidak valid atau ID kosong.');

        $data = toSQL($store, $obj);

        // Cek apakah sudah ada
        $st = $pdo->prepare("SELECT id FROM `$table` WHERE id = ?");
        $st->execute([$data['id']]);
        $exists = $st->fetchColumn();

        if ($exists) {
            // UPDATE
            $id = $data['id'];
            unset($data['id']);
            if (empty($data)) out(true, ['id' => $id], 'Tidak ada perubahan.');
            $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
            $vals = array_values($data);
            $vals[] = $id;
            $pdo->prepare("UPDATE `$table` SET $set WHERE id = ?")->execute($vals);
        } else {
            // INSERT
            $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
            $phs  = implode(', ', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($phs)")->execute(array_values($data));
        }

        out(true, ['id' => $obj['id']], $exists ? 'Data diperbarui.' : 'Data ditambahkan.');
    }

    // ── DELETE ────────────────────────────────────────────
    if ($action === 'delete') {
        $data = body();
        $id   = $data['id'] ?? null;
        if (!$id) out(false, null, 'ID tidak boleh kosong.');
        $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
        out(true, ['id' => $id], 'Data dihapus.');
    }

    // ── CLEAR ─────────────────────────────────────────────
    if ($action === 'clear') {
        $pdo->exec("DELETE FROM `$table`");
        out(true, null, "Semua data $store dihapus.");
    }

    // ── LOGIN ADMIN ───────────────────────────────────────
    if ($action === 'login') {
        $data = body();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $st = $pdo->prepare("SELECT * FROM adminusers WHERE username = ? AND status = 'Aktif'");
        $st->execute([$username]);
        $user = $st->fetch();

        if ($user && $user['password'] === $password) {
            out(true, toJS('adminusers', $user), 'Login berhasil.');
        }
        out(false, null, 'Username atau password salah!');
    }

    // ── LOGIN PASIEN (by NIK + TTL) ───────────────────────
    if ($action === 'loginPasien') {
        $data = body();
        $nik  = $data['nik'] ?? '';
        $ttl  = $data['ttl'] ?? '';
        $nama = $data['nama'] ?? '';

        $st = $pdo->prepare("SELECT * FROM pasien WHERE nik = ? AND ttl = ?");
        $st->execute([$nik, $ttl]);
        $found = $st->fetch();

        if ($found) {
            out(true, toJS('pasien', $found), 'Login berhasil.');
        }

        // Auto-register pasien baru
        if ($nama) {
            $newId = dechex(time()) . substr(md5(rand()), 0, 4);
            $pdo->prepare("INSERT INTO pasien (id, nama, nik, ttl, jk, telepon, email, alamat) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$newId, $nama, $nik, $ttl, '', '', '', '']);
            $st = $pdo->prepare("SELECT * FROM pasien WHERE id = ?"); $st->execute([$newId]);
            out(true, toJS('pasien', $st->fetch()), 'Akun baru dibuat.');
        }

        out(false, null, 'Pasien tidak ditemukan. Masukkan nama untuk membuat akun.');
    }

    out(false, null, 'Action tidak dikenal: ' . $action);

} catch (PDOException $e) {
    http_response_code(500);
    out(false, null, 'Database error: ' . $e->getMessage());
} catch (Throwable $e) {
    http_response_code(500);
    out(false, null, 'Server error: ' . $e->getMessage());
}
