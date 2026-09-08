<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../includes/Category.php';

$pdo = getDbConnection();
$members = $pdo->query(
    "SELECT m.member_code, m.full_name, c.name AS category_name, m.status, m.valid_upto
     FROM members m JOIN categories c ON c.id = m.category_id
     ORDER BY m.id DESC LIMIT 50"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 0.85rem; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .nav a { margin-right: 16px; font-weight: 600; color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card-box" style="max-width: 800px;">
        <div class="nav">
            <a href="categories.php">Categories</a>
            <a href="instructions.php">Instructions</a>
            <a href="facilities.php">Facilities</a>
            <a href="add-member.php">+ New Member</a>
            <a href="logout.php" style="float:right; color:#b91c1c;">Log out</a>
        </div>
        <h1>Recent Members</h1>
        <table>
            <tr><th>Code</th><th>Name</th><th>Category</th><th>Valid Upto</th><th>Status</th><th>Card</th></tr>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['member_code']) ?></td>
                    <td><?= htmlspecialchars($m['full_name']) ?></td>
                    <td><?= htmlspecialchars($m['category_name']) ?></td>
                    <td><?= htmlspecialchars($m['valid_upto']) ?></td>
                    <td><?= htmlspecialchars($m['status']) ?></td>
                    <td><a href="../public/card.php?code=<?= urlencode($m['member_code']) ?>">Download</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
