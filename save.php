<?php
header('Content-Type: application/json');

// Перевірка наявності необхідних даних
if (empty($_POST['name']) || empty($_POST['date']) || empty($_FILES['photos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Не всі дані надіслані']);
    exit;
}

$name = trim($_POST['name']);
$date = $_POST['date'];
$photos = $_FILES['photos'];

// Створення папок, якщо їх немає
$baseDir = 'violators';
$dateDir = $baseDir . '/' . $date;
$violatorDir = $dateDir . '/' . uniqid();

if (!file_exists($baseDir)) mkdir($baseDir, 0777, true);
if (!file_exists($dateDir)) mkdir($dateDir, 0777, true);
mkdir($violatorDir, 0777, true);

// Збереження фото
$savedImages = [];
foreach ($photos['tmp_name'] as $key => $tmpName) {
    if ($photos['error'][$key] !== UPLOAD_ERR_OK) continue;
    
    $ext = pathinfo($photos['name'][$key], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $destination = $violatorDir . '/' . $filename;
    
    if (move_uploaded_file($tmpName, $destination)) {
        $savedImages[] = $filename;
    }
}

if (empty($savedImages)) {
    echo json_encode(['status' => 'error', 'message' => 'Не вдалося зберегти фото']);
    exit;
}

// Створення запису про порушника
$violatorData = [
    'name' => $name,
    'date' => $date,
    'folder' => basename($violatorDir),
    'images' => $savedImages,
    'created_at' => date('Y-m-d H:i:s')
];

file_put_contents($violatorDir . '/data.json', json_encode($violatorData));

echo json_encode(['status' => 'success']);
?>