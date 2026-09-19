<?php
$content = file_get_contents('c:/xampp/htdocs/JT_Purchase/admin/dashboard_backup.php');
preg_match('/<!-- 1\. 4 KARTU METRIK UTAMA.*?<\/div>\s*<\/div>\s*<\/div>/s', $content, $htmlMatch);
preg_match('/async function loadFinanceDashboard\(\).*?\}\s*\}/s', $content, $jsMatch);

$financePhp = $htmlMatch[0] . "\n\n<script>\n" . $jsMatch[0] . "\n\ndocument.addEventListener('DOMContentLoaded', loadFinanceDashboard);\n</script>";

file_put_contents('c:/xampp/htdocs/JT_Purchase/admin/components/dashboards/finance.php', $financePhp);
echo "finance.php created";
