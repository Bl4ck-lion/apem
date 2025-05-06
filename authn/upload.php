<?php
// upload.php

// Matikan tampilan error ke output agar JSON murni
ini_set('display_errors', 0);
error_reporting(0);

// header JSON
header('Content-Type: application/json; charset=utf-8');

// lokasi penyimpanan
$uploadBase = __DIR__ . '/uploads/';
$dataFile   = __DIR__ . '/data/registrations.json';

// pastikan folder uploads & data ada
foreach ([$uploadBase, dirname($dataFile)] as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}

// helper response JSON
function resp($status, $data = []) {
    // bersihkan output buffer sebelum mengirim JSON
    if (ob_get_length()) ob_clean();
    echo json_encode(array_merge(['status' => $status], $data), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// 1) ambil & validasi field
$req = ['nama','email','hp','sekolah','bidang','jenis','username','password'];
foreach ($req as $f) {
    if (empty($_POST[$f])) {
        http_response_code(400);
        resp('error', ['message' => "Field “$f” wajib diisi."]);
    }
}

$nama     = trim($_POST['nama']);
$email    = $_POST['email'];
$hp       = $_POST['hp'];
$sekolah  = $_POST['sekolah'];
$bidang   = $_POST['bidang'];
$jenis    = $_POST['jenis'];   // gratis atau berbayar
$username = $_POST['username'];
$password = $_POST['password']; // langsung simpan plaintext

// 2) siapkan folder target berdasarkan jenis & nama
$sanitized = preg_replace('/[^A-Za-z0-9_-]/', '_', $nama);
$targetDir = "$uploadBase$jenis/$sanitized/";
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    http_response_code(500);
    resp('error', ['message' => 'Gagal membuat direktori upload.']);
}

// 3) proses upload file
$filesInfo = [];
if ($jenis === 'gratis') {
    // multiple bukti_gratis[]
    if (!empty($_FILES['bukti_gratis']['tmp_name'])) {
        foreach ($_FILES['bukti_gratis']['tmp_name'] as $i => $tmp) {
            if (is_uploaded_file($tmp)) {
                $orig = basename($_FILES['bukti_gratis']['name'][$i]);
                $ext  = pathinfo($orig, PATHINFO_EXTENSION);
                $new  = uniqid('G_') . ".$ext";
                if (move_uploaded_file($tmp, "$targetDir$new")) {
                    $filesInfo[] = [
                        'original' => $orig,
                        'stored'   => $new,
                        'path'     => "uploads/$jenis/$sanitized/$new"
                    ];
                }
            }
        }
    }
} else {
    // single bukti_bayar
    if (!empty($_FILES['bukti_bayar']['tmp_name']) && is_uploaded_file($_FILES['bukti_bayar']['tmp_name'])) {
        $orig = basename($_FILES['bukti_bayar']['name']);
        $ext  = pathinfo($orig, PATHINFO_EXTENSION);
        $new  = uniqid('B_') . ".$ext";
        if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], "$targetDir$new")) {
            $filesInfo[] = [
                'original' => $orig,
                'stored'   => $new,
                'path'     => "uploads/$jenis/$sanitized/$new"
            ];
        }
    }
}

// 4) baca & update registrations.json
$all = file_exists($dataFile)
    ? json_decode(file_get_contents($dataFile), true) ?: []
    : [];

$entry = [
    'id'        => uniqid('reg_'),
    'nama'      => $nama,
    'email'     => $email,
    'hp'        => $hp,
    'sekolah'   => $sekolah,
    'bidang'    => $bidang,
    'jenis'     => $jenis,
    'username'  => $username,
    'password'  => $password,
    'files'     => $filesInfo,
    'createdAt' => date('c'),
];

$all[] = $entry;
file_put_contents($dataFile, json_encode($all, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);

// 5) kembalikan response
resp('success', [
    'message'      => 'Pendaftaran berhasil disimpan.',
    'registration' => $entry
]);
