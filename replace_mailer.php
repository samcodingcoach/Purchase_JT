<?php
$file = 'c:/xampp/htdocs/JT_Purchase/config/mailer.php';
$content = file_get_contents($file);

// 1. Double quoted subjects with " - PT Jaya Teknis"
$content = str_replace(' - PT Jaya Teknis";', ' - " . getCompanyProfile()[\'nama\'];', $content);

// 2. PO Subject update based on user request: Subject misal [PO: XXXX] - NAMA PERUSAHAAN
$poSubjectOld = '$subject = "[PO TERBIT] Request Order Telah Disetujui Purchasing: {$roData[\'nomor\']}{$poInfo}";';
$poSubjectNew = '$poNum = !empty($roData[\'nomor_po\']) ? $roData[\'nomor_po\'] : \'-\';' . "\n" . '                $subject = "[PO: {$poNum}] - " . getCompanyProfile()[\'nama\'];';
$content = str_replace($poSubjectOld, $poSubjectNew, $content);

// 3. Single quoted / HTML block PT Jaya Teknis
$content = str_replace('PT Jaya Teknis', '\' . getCompanyProfile()[\'nama\'] . \'', $content);
$content = str_replace('PT JAYA TEKNIS', '\' . strtoupper(getCompanyProfile()[\'nama\']) . \'', $content);

file_put_contents($file, $content);
echo "Replaced successfully\n";
