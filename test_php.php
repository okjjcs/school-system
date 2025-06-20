<?php
echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>اختبار PHP</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class='bg-light'>
<div class='container mt-5'>
<div class='row justify-content-center'>
<div class='col-md-6'>
<div class='card'>
<div class='card-header bg-success text-white text-center'>
<h4>✅ PHP يعمل بشكل صحيح!</h4>
</div>
<div class='card-body'>";

echo "<div class='alert alert-success text-center'>
        <h5>🎉 ممتاز! PHP يعمل</h5>
        <p>إصدار PHP: " . phpversion() . "</p>
      </div>";

// اختبار SQLite
if (extension_loaded('sqlite3')) {
    echo "<div class='alert alert-success'>✅ SQLite3 متوفر</div>";
} else {
    echo "<div class='alert alert-danger'>❌ SQLite3 غير متوفر</div>";
}

// اختبار PDO
if (extension_loaded('pdo')) {
    echo "<div class='alert alert-success'>✅ PDO متوفر</div>";
} else {
    echo "<div class='alert alert-danger'>❌ PDO غير متوفر</div>";
}

// اختبار PDO SQLite
if (extension_loaded('pdo_sqlite')) {
    echo "<div class='alert alert-success'>✅ PDO SQLite متوفر</div>";
} else {
    echo "<div class='alert alert-danger'>❌ PDO SQLite غير متوفر</div>";
}

echo "<div class='text-center mt-4'>
        <a href='setup_fixed.php' class='btn btn-primary btn-lg'>
            🚀 إعداد النظام الآن
        </a>
      </div>";

echo "</div>
</div>
</div>
</div>
</div>
</body>
</html>";
?>
