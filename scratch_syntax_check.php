<?php
$scanDirs = ['admin', 'api', 'config'];
$syntaxErrors = [];
$totalFiles = 0;

foreach ($scanDirs as $sDir) {
    if (!is_dir($sDir)) continue;
    $ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDir));
    foreach ($ite as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $totalFiles++;
            $path = $file->getPathname();
            $out = [];
            $ret = 0;
            exec("C:\\xampp\\php\\php.exe -l \"$path\"", $out, $ret);
            if ($ret !== 0) {
                $syntaxErrors[] = "$path: " . implode(' ', $out);
            }
        }
    }
}

echo "Total PHP Files Scanned: $totalFiles\n";
echo "Syntax Errors Found: " . count($syntaxErrors) . "\n";
foreach ($syntaxErrors as $err) {
    echo " - $err\n";
}
