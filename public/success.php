<?php
require_once __DIR__ . '/../includes/Member.php';

$code = $_GET['code'] ?? '';
$pdo = getDbConnection();
$member = $code ? getMemberByCode($pdo, $code) : null;

if (!$member) {
    http_response_code(404);
    die('Member not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome, <?= htmlspecialchars($member['full_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="card-box success-box">
        <h1>You're all set, <?= htmlspecialchars(explode(' ', $member['full_name'])[0]) ?>!</h1>
        <p class="subtitle">Your membership has been created.</p>
        <div class="code"><?= htmlspecialchars($member['member_code']) ?></div>
        <p style="color:#6b7280; margin-top:-12px;"><?= htmlspecialchars($member['category_name']) ?></p>
        <a class="btn-download" href="card.php?code=<?= urlencode($member['member_code']) ?>">
            Download Membership Card (PDF)
        </a>
    </div>
</body>
</html>
