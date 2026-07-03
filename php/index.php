<?php
// ============================================================
// index.php — Halaman Admin Server-Side Rendering (PHP)
// ============================================================
// Akses: http://localhost/klinik_pratama/index.php
// Login default: admin / admin123
// ============================================================

require_once __DIR__ . '/config.php';
session_start();

$pdo   = getDB();
$today = date('Y-m-d');

// ── Auth ───────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $st   = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'Aktif'");
    $st->execute([$user]);
    $found = $st->fetch();
    if ($found && $found['password'] === $pass) {
        $_SESSION['admin'] = [
            'id'        => $found['id'],
            'nama'      => $found['nama'],
            'username'  => $found['username'],
            'role'      => $found['role'],
            'hak_akses' => json_decode($found['hak_akses'], true),
        ];
        header('Location: index.php'); exit;
    }
    $loginError = 'Username atau password salah!';
}

if (isset($_GET['logout'])) {
    session_destroy(); header('Location: index.php'); exit;
}

$admin = $_SESSION['admin'] ?? null;

// ── Helpers ────────────────────────────────────────────────
function hasAccess(string $menu): bool {
    return in_array($menu, $_SESSION['admin']['hak_akses'] ?? []);
}

function rupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// ── Data untuk halaman yang aktif ──────────────────────────
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin – Klinik Pratama</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f6faff; }
    .sidebar-link { display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:10px;font-size:14px;color:#4b5563;transition:.15s; }
    .sidebar-link:hover { background:#e8f0fe;color:#0057B8; }
    .sidebar-link.active { background:#e8f0fe;color:#0057B8;font-weight:600; }
    .badge { display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600; }
    table th { background:#f9fafb;text-align:left;padding:10px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em; }
    table td { padding:12px 16px;font-size:14px;border-top:1px solid #f3f4f6; }
    tr:hover td { background:#f0f7ff; }
  </style>
</head>
<body>

<?php if (!$admin): ?>
<!-- ── LOGIN ──────────────────────────────────────────────── -->
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-700 to-teal-500">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8">
    <div class="text-center mb-6">
      <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20A10 10 0 0012 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-800">Klinik Pratama</h1>
      <p class="text-sm text-gray-500">Sistem Manajemen Klinik</p>
    </div>
    <?php if (!empty($loginError)): ?>
      <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3 mb-4"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login"/>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input name="username" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 outline-none" placeholder="admin" required/>
      </div>
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 outline-none" placeholder="••••••••" required/>
      </div>
      <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl transition">Masuk</button>
    </form>
    <p class="text-xs text-center text-gray-400 mt-5">Demo: admin / admin123</p>
  </div>
</div>

<?php else: ?>
<!-- ── ADMIN PANEL ─────────────────────────────────────────── -->
<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-60 bg-white border-r border-gray-100 flex flex-col flex-shrink-0">
    <div class="px-5 py-5 border-b border-gray-100">
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center">
          <span class="text-white text-lg font-bold">K</span>
        </div>
        <div>
          <div class="font-bold text-gray-800 text-sm">Klinik Pratama</div>
          <div class="text-xs text-gray-400"><?= htmlspecialchars($admin['role']) ?></div>
        </div>
      </div>
    </div>
    <nav class="flex-1 overflow-y-auto p-3 space-y-1">
      <?php
      $menus = [
        'dashboard'    => ['icon'=>'📊','label'=>'Dashboard'],
        'pasien'       => ['icon'=>'👥','label'=>'Pasien'],
        'dokter'       => ['icon'=>'🩺','label'=>'Dokter'],
        'layanan'      => ['icon'=>'🏥','label'=>'Layanan'],
        'pendaftaran'  => ['icon'=>'📋','label'=>'Pendaftaran'],
        'jadwal'       => ['icon'=>'📅','label'=>'Jadwal'],
        'rekam_medis'  => ['icon'=>'📝','label'=>'Rekam Medis'],
        'obat'         => ['icon'=>'💊','label'=>'Obat'],
        'billing'      => ['icon'=>'💳','label'=>'Billing'],
        'pesan'        => ['icon'=>'✉️','label'=>'Pesan'],
        'admin_users'  => ['icon'=>'👤','label'=>'Users'],
      ];
      foreach ($menus as $key => $m):
        if (!hasAccess($key === 'admin_users' ? 'users' : $key)) continue;
        $active = ($page === $key) ? 'active' : '';
      ?>
        <a href="?page=<?= $key ?>" class="sidebar-link <?= $active ?>">
          <span><?= $m['icon'] ?></span> <?= $m['label'] ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="p-4 border-t border-gray-100">
      <div class="text-xs text-gray-500 mb-1"><?= htmlspecialchars($admin['nama']) ?></div>
      <a href="?logout=1" class="text-xs text-red-500 hover:underline">Keluar</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6">

  <?php
  // ════════════════════════════════════════════════════════
  // DASHBOARD
  // ════════════════════════════════════════════════════════
  if ($page === 'dashboard'):
    $totalPasien   = (int)$pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
    $regToday      = (int)$pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE tanggal=?")->execute([$today]) &&
                     false; // reset; pakai cara benar:
    $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE tanggal=?"); $st->execute([$today]);
    $regToday = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT COALESCE(SUM(total_biaya),0) FROM billing WHERE tanggal=? AND status='Lunas'"); $st->execute([$today]);
    $pendapatanHariIni = (int)$st->fetchColumn();
    $totalPendapatan = (int)$pdo->query("SELECT COALESCE(SUM(total_biaya),0) FROM billing WHERE status='Lunas'")->fetchColumn();
    $obatHabis  = (int)$pdo->query("SELECT COUNT(*) FROM obat WHERE status='Habis'")->fetchColumn();
    $belumLunas = (int)$pdo->query("SELECT COUNT(*) FROM billing WHERE status='Belum Lunas'")->fetchColumn();

    $recentReg = $pdo->prepare("SELECT * FROM pendaftaran ORDER BY created_at DESC LIMIT 8"); $recentReg->execute();
    $recentReg = $recentReg->fetchAll();

    $statusColors = ['Menunggu'=>'bg-yellow-100 text-yellow-700','Diproses'=>'bg-blue-100 text-blue-700','Selesai'=>'bg-green-100 text-green-700','Batal'=>'bg-red-100 text-red-600'];
  ?>
    <h2 class="text-xl font-bold text-gray-800 mb-6">Dashboard</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-3xl font-bold text-blue-600"><?= number_format($totalPasien) ?></div><div class="text-sm text-gray-400 mt-1">Total Pasien</div></div>
      <div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-3xl font-bold text-teal-500"><?= $regToday ?></div><div class="text-sm text-gray-400 mt-1">Pendaftaran Hari Ini</div></div>
      <div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-xl font-bold text-green-600"><?= rupiah($pendapatanHariIni) ?></div><div class="text-sm text-gray-400 mt-1">Pendapatan Hari Ini</div></div>
      <div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-xl font-bold text-gray-800"><?= rupiah($totalPendapatan) ?></div><div class="text-sm text-gray-400 mt-1">Total Pendapatan</div></div>
    </div>
    <div class="grid grid-cols-2 gap-4 mb-8">
      <div class="bg-white rounded-2xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-2xl">💊</div>
        <div><div class="text-2xl font-bold text-red-500"><?= $obatHabis ?></div><div class="text-sm text-gray-400">Obat Stok Habis</div></div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center text-2xl">💳</div>
        <div><div class="text-2xl font-bold text-yellow-500"><?= $belumLunas ?></div><div class="text-sm text-gray-400">Tagihan Belum Lunas</div></div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b font-semibold text-gray-700">Pendaftaran Terbaru</div>
      <table class="w-full"><thead><tr><th>No. Reg</th><th>Pasien</th><th>Layanan</th><th>Dokter</th><th>Tanggal</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recentReg as $r): ?>
        <tr>
          <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($r['no_reg']) ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['pasien_nama']) ?></td>
          <td><?= htmlspecialchars($r['layanan_nama']) ?></td>
          <td><?= htmlspecialchars($r['dokter_nama']) ?></td>
          <td class="text-gray-400"><?= $r['tanggal'] ?></td>
          <td><span class="badge <?= $statusColors[$r['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= $r['status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // PASIEN
  // ════════════════════════════════════════════════════════
  elseif ($page === 'pasien' && hasAccess('pasien')):
    $q  = $_GET['q'] ?? '';
    $st = $q
      ? $pdo->prepare("SELECT * FROM pasien WHERE nama LIKE ? OR nik LIKE ? OR telepon LIKE ? ORDER BY created_at DESC")
      : $pdo->prepare("SELECT * FROM pasien ORDER BY created_at DESC");
    $q ? $st->execute(["%$q%","%$q%","%$q%"]) : $st->execute();
    $rows = $st->fetchAll();
  ?>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-800">Data Pasien</h2>
      <span class="text-sm text-gray-400"><?= count($rows) ?> data</span>
    </div>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex gap-3">
        <form method="GET" class="flex gap-2 flex-1">
          <input type="hidden" name="page" value="pasien"/>
          <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama / NIK / telepon..." class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none"/>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">Cari</button>
        </form>
      </div>
      <div class="overflow-x-auto">
      <table class="w-full"><thead><tr><th>No</th><th>Nama</th><th>NIK</th><th>TTL</th><th>JK</th><th>Telepon</th><th>Alamat</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="text-gray-400"><?= $i+1 ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['nama']) ?></td>
          <td class="font-mono text-xs"><?= htmlspecialchars($r['nik']) ?></td>
          <td><?= $r['ttl'] ?></td>
          <td><?= $r['jk'] ?></td>
          <td><?= htmlspecialchars($r['telepon'] ?? '-') ?></td>
          <td class="text-gray-500 max-w-[200px] truncate"><?= htmlspecialchars($r['alamat'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // PENDAFTARAN
  // ════════════════════════════════════════════════════════
  elseif ($page === 'pendaftaran' && hasAccess('pendaftaran')):
    $filterTgl = $_GET['tanggal'] ?? $today;
    $st = $pdo->prepare("SELECT * FROM pendaftaran WHERE tanggal = ? ORDER BY created_at ASC");
    $st->execute([$filterTgl]);
    $rows = $st->fetchAll();
    $statusColors = ['Menunggu'=>'bg-yellow-100 text-yellow-700','Diproses'=>'bg-blue-100 text-blue-700','Selesai'=>'bg-green-100 text-green-700','Batal'=>'bg-red-100 text-red-600'];
  ?>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-800">Pendaftaran</h2>
    </div>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex gap-3 items-center">
        <form method="GET" class="flex gap-2">
          <input type="hidden" name="page" value="pendaftaran"/>
          <input type="date" name="tanggal" value="<?= $filterTgl ?>" class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none"/>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
        </form>
        <span class="text-sm text-gray-400"><?= count($rows) ?> data</span>
      </div>
      <div class="overflow-x-auto">
      <table class="w-full"><thead><tr><th>No. Reg</th><th>Pasien</th><th>Layanan</th><th>Dokter</th><th>Keluhan</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($r['no_reg']) ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['pasien_nama']) ?></td>
          <td><?= htmlspecialchars($r['layanan_nama']) ?></td>
          <td><?= htmlspecialchars($r['dokter_nama']) ?></td>
          <td class="text-gray-500 max-w-[220px]"><div class="line-clamp-2"><?= htmlspecialchars($r['keluhan']) ?></div></td>
          <td><span class="badge <?= $statusColors[$r['status']] ?? '' ?>"><?= $r['status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // OBAT
  // ════════════════════════════════════════════════════════
  elseif ($page === 'obat' && hasAccess('obat')):
    $q  = $_GET['q'] ?? '';
    $st = $q
      ? $pdo->prepare("SELECT * FROM obat WHERE nama LIKE ? OR kategori LIKE ? ORDER BY nama")
      : $pdo->prepare("SELECT * FROM obat ORDER BY nama");
    $q ? $st->execute(["%$q%","%$q%"]) : $st->execute();
    $rows = $st->fetchAll();
    $statusColors = ['Tersedia'=>'bg-green-100 text-green-700','Habis'=>'bg-red-100 text-red-600','Nonaktif'=>'bg-gray-100 text-gray-400'];
  ?>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-800">Data Obat</h2>
    </div>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex gap-3">
        <form method="GET" class="flex gap-2 flex-1">
          <input type="hidden" name="page" value="obat"/>
          <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama / kategori..." class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none"/>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">Cari</button>
        </form>
      </div>
      <div class="overflow-x-auto">
      <table class="w-full"><thead><tr><th>No</th><th>Nama Obat</th><th>Kategori</th><th>Satuan</th><th>Stok</th><th>Harga</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="text-gray-400"><?= $i+1 ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['nama']) ?></td>
          <td class="text-gray-500"><?= htmlspecialchars($r['kategori']) ?></td>
          <td><?= htmlspecialchars($r['satuan']) ?></td>
          <td class="font-semibold <?= $r['stok'] == 0 ? 'text-red-500' : ($r['stok'] < 50 ? 'text-yellow-500' : 'text-gray-800') ?>"><?= number_format($r['stok']) ?></td>
          <td><?= rupiah((int)$r['harga']) ?></td>
          <td><span class="badge <?= $statusColors[$r['status']] ?? '' ?>"><?= $r['status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // BILLING
  // ════════════════════════════════════════════════════════
  elseif ($page === 'billing' && hasAccess('billing')):
    $filterStatus = $_GET['status'] ?? '';
    $sql = $filterStatus
      ? "SELECT * FROM billing WHERE status = ? ORDER BY created_at DESC"
      : "SELECT * FROM billing ORDER BY created_at DESC";
    $st = $pdo->prepare($sql);
    $filterStatus ? $st->execute([$filterStatus]) : $st->execute();
    $rows = $st->fetchAll();
    $totalLunas     = array_sum(array_column(array_filter($rows, fn($r)=>$r['status']==='Lunas'), 'total_biaya'));
    $totalBelumLunas = count(array_filter($rows, fn($r)=>$r['status']==='Belum Lunas'));
    $statusColors = ['Lunas'=>'bg-green-100 text-green-700','Belum Lunas'=>'bg-yellow-100 text-yellow-700','Dibatalkan'=>'bg-red-100 text-red-600'];
  ?>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-800">Billing & Tagihan</h2>
    </div>
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-xl font-bold text-green-600"><?= rupiah((int)$totalLunas) ?></div><div class="text-sm text-gray-400">Total Pendapatan (Lunas)</div></div>
      <div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-xl font-bold text-yellow-500"><?= $totalBelumLunas ?></div><div class="text-sm text-gray-400">Tagihan Belum Lunas</div></div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex gap-3 items-center">
        <form method="GET" class="flex gap-2">
          <input type="hidden" name="page" value="billing"/>
          <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none">
            <option value="">Semua Status</option>
            <?php foreach (['Lunas','Belum Lunas','Dibatalkan'] as $s): ?>
              <option <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
        </form>
      </div>
      <div class="overflow-x-auto">
      <table class="w-full"><thead><tr><th>No. Tagihan</th><th>Pasien</th><th>Layanan</th><th>Total</th><th>Metode</th><th>Tanggal</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($r['no_bill']) ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['pasien_nama']) ?></td>
          <td><?= htmlspecialchars($r['layanan_nama']) ?></td>
          <td class="font-semibold"><?= rupiah((int)$r['total_biaya']) ?></td>
          <td class="text-gray-400"><?= htmlspecialchars($r['metode_pembayaran'] ?? '-') ?></td>
          <td class="text-gray-400"><?= $r['tanggal'] ?></td>
          <td><span class="badge <?= $statusColors[$r['status']] ?? '' ?>"><?= $r['status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // REKAM MEDIS
  // ════════════════════════════════════════════════════════
  elseif ($page === 'rekam_medis' && hasAccess('rekammedis')):
    $q = $_GET['q'] ?? '';
    $st = $q
      ? $pdo->prepare("SELECT * FROM rekam_medis WHERE pasien_nama LIKE ? OR dokter_nama LIKE ? OR diagnosis LIKE ? ORDER BY tanggal DESC")
      : $pdo->prepare("SELECT * FROM rekam_medis ORDER BY tanggal DESC");
    $q ? $st->execute(["%$q%","%$q%","%$q%"]) : $st->execute();
    $rows = $st->fetchAll();
  ?>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-800">Rekam Medis</h2>
    </div>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex gap-3">
        <form method="GET" class="flex gap-2 flex-1">
          <input type="hidden" name="page" value="rekam_medis"/>
          <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari pasien / dokter / diagnosis..." class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none"/>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">Cari</button>
        </form>
      </div>
      <div class="overflow-x-auto">
      <table class="w-full"><thead><tr><th>Tanggal</th><th>Pasien</th><th>Dokter</th><th>Diagnosis</th><th>TD</th><th>BB/TB</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-gray-400 whitespace-nowrap"><?= $r['tanggal'] ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['pasien_nama']) ?></td>
          <td class="text-gray-500"><?= htmlspecialchars($r['dokter_nama']) ?></td>
          <td class="max-w-[220px]"><div class="line-clamp-2"><?= htmlspecialchars($r['diagnosis']) ?></div></td>
          <td class="text-gray-400 whitespace-nowrap"><?= htmlspecialchars($r['tekanan_darah'] ?? '-') ?></td>
          <td class="text-gray-400 whitespace-nowrap"><?= $r['berat_badan'] ?>kg / <?= $r['tinggi_badan'] ?>cm</td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // DOKTER
  // ════════════════════════════════════════════════════════
  elseif ($page === 'dokter' && hasAccess('dokter')):
    $rows = $pdo->query("SELECT d.*, l.nama AS layanan_nama FROM dokter d LEFT JOIN layanan l ON l.id=d.layanan_id ORDER BY d.nama")->fetchAll();
  ?>
    <h2 class="text-xl font-bold text-gray-800 mb-6">Data Dokter</h2>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
      <table class="w-full"><thead><tr><th>No</th><th>Nama Dokter</th><th>Spesialisasi</th><th>Layanan</th><th>Telepon</th><th>Hari Praktik</th><th>Jam</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="text-gray-400"><?= $i+1 ?></td>
          <td class="font-medium"><?= htmlspecialchars($r['nama']) ?></td>
          <td class="text-gray-500"><?= htmlspecialchars($r['spesialisasi']) ?></td>
          <td><?= htmlspecialchars($r['layanan_nama'] ?? '-') ?></td>
          <td class="text-gray-400"><?= htmlspecialchars($r['telepon'] ?? '-') ?></td>
          <td class="text-gray-500"><?= htmlspecialchars($r['hari'] ?? '-') ?></td>
          <td class="text-gray-400"><?= htmlspecialchars($r['jam'] ?? '-') ?></td>
          <td><span class="badge <?= $r['status']==='Aktif'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500' ?>"><?= $r['status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>

  <?php
  // ════════════════════════════════════════════════════════
  // DEFAULT — halaman belum diimplementasikan
  // ════════════════════════════════════════════════════════
  else:
  ?>
    <div class="flex flex-col items-center justify-center h-96 text-gray-400">
      <div class="text-6xl mb-4">🚧</div>
      <div class="font-semibold">Halaman <strong><?= htmlspecialchars($page) ?></strong> tersedia via REST API.</div>
      <div class="text-sm mt-2">Gunakan <code class="bg-gray-100 px-2 py-1 rounded">api.php?resource=<?= htmlspecialchars($page) ?>&action=list</code></div>
    </div>
  <?php endif; ?>

  </main>
</div><!-- end admin panel -->
<?php endif; ?>

</body>
</html>
