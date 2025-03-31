<?php
header('Content-Type: application/json');

$baseDir = 'violators';
$result = [];

if (!file_exists($baseDir)) {
    echo json_encode($result);
    exit;
}

// Скануємо папки з датами
$dateDirs = array_filter(glob($baseDir . '/*'), 'is_dir');

foreach ($dateDirs as $dateDir) {
    $date = basename($dateDir);
    $result[$date] = [];
    
    // Скануємо папки з порушниками
    $violatorDirs = array_filter(glob($dateDir . '/*'), 'is_dir');
    
    foreach ($violatorDirs as $violatorDir) {
        $dataFile = $violatorDir . '/data.json';
        
        if (file_exists($dataFile)) {
            $data = json_decode(file_get_contents($dataFile), true);
            if ($data) {
                $result[$date][] = $data;
            }
        }
    }
}

echo json_encode($result);
?>