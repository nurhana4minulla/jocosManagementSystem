<?php
$hash = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['new_password'])) {
    $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>IT Admin - Password Generator</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f8fafc; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; }
        input[type="text"] { width: 100%; padding: 10px; margin-bottom: 10px; box-sizing: border-box; }
        button { padding: 10px 20px; background: #0F172A; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .result { margin-top: 20px; padding: 15px; background: #e2e8f0; word-wrap: break-word; font-family: monospace; }
    </style>
</head>
<body>
    <div class="box">
        <h3>DTI-IX IT Tool: Hash Generator</h3>
        <p>Type the raw password below to generate a secure database hash.</p>
        <form method="POST">
            <input type="text" name="new_password" placeholder="Enter new password (e.g. DTIadmin2026!)" required>
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php if($hash): ?>
            <div class="result">
                <strong>Copy this Hash to phpMyAdmin:</strong><br><br>
                <?php echo $hash; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>