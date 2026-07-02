<?php
echo "<h2>File di folder uploads/</h2>";
$files = scandir('uploads/');
echo "<ul>";
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        $path = 'uploads/' . $file;
        echo "<li>$file - " . (file_exists($path) ? '✅ ADA' : '❌ TIDAK ADA') . " - Size: " . filesize($path) . " bytes</li>";
    }
}
echo "</ul>";

echo "<h2>Data Ruangan dari Database</h2>";
include 'database.php';
$query = "SELECT id, nama, gambar FROM rooms ORDER BY id";
$result = query($query);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nama</th><th>Gambar di DB</th><th>File Ada?</th><th>Preview</th></tr>";
while($row = fetchOne($result)) {
    $path = 'uploads/' . $row['gambar'];
    $exists = file_exists($path);
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nama']}</td>";
    echo "<td>{$row['gambar']}</td>";
    echo "<td>" . ($exists ? '✅' : '❌') . "</td>";
    echo "<td>";
    if($exists) {
        echo "<img src='$path' style='width:100px; height:60px; object-fit:cover;'>";
    } else {
        echo "File tidak ditemukan";
        // Coba cek dengan ekstensi lain
        $altPath = str_replace('.jpeg', '.jpg', $path);
        if(file_exists($altPath)) {
            echo " (Tapi ada file .jpg: " . basename($altPath) . ")";
        }
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
?>