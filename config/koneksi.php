<?php
/**
 * Koneksi Database Terpusat - PT Jaya Teknis
 * Menggunakan ekstensi mysqli native dengan prepared statement support.
 */

// Laporkan error mysqli sebagai exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host = '127.0.0.1';
$db_user = 'matos';
$db_pass = '1234';
$db_name = 'jaya_teknis';
$db_port = 3306;

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Coba fallback jika nama database di server adalah jaya_teknik
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, 'jaya_teknik', $db_port);
        $conn->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e2) {
        // Coba fallback user root tanpa password jika default XAMPP aktif untuk kemudahan testing
        try {
            $conn = new mysqli($db_host, 'root', '', 'jaya_teknis', $db_port);
            $conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e3) {
            try {
                $conn = new mysqli($db_host, 'root', '', 'jaya_teknik', $db_port);
                $conn->set_charset("utf8mb4");
            } catch (mysqli_sql_exception $e4) {
                error_log("Database Connection Error: " . $e->getMessage());
                if (basename($_SERVER['PHP_SELF']) === 'koneksi.php' || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false)) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Koneksi ke database gagal. Pastikan database server aktif dan konfigurasi sesuai.',
                        'error_detail' => $e->getMessage()
                    ]);
                    exit;
                }
                die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
            }
        }
    }
}
