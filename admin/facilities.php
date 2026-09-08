<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' && trim($_POST['name'] ?? '') !== '') {
        $stmt = $pdo->prepare(
            "INSERT INTO facilities (name, discount_text, sort_order) VALUES (:name, :discount, :sort)"
        );
        $stmt->execute([
            'name' => trim($_POST['name']),
            'discount' => trim($_POST['discount_text'] ?? ''),
            'sort' => (int) ($_POST['sort_order'] ?? 0),
        ]);
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM facilities WHERE id = :id");
        $stmt->execute(['id' => (int) $_POST['id']]);
    }
}

$facilities = $pdo->query("SELECT * FROM facilities ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Facilities</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 0.85rem; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .nav a { margin-right: 16px; font-weight: 600; color: #4f46e5; text-decoration: none; }
        form.inline { display: inline; }
    </style>
</head>
<body>
    <div class="card-box" style="max-width: 700px;">
        <div class="nav"><a href="index.php">← Dashboard</a></div>
        <h1>Facilities & Discounts</h1>
        <p class="subtitle">
            Shown as text badges on the card back (no logos, to avoid using third-party trademarks
            without permission — see note below).
        </p>

        <table>
            <tr><th>#</th><th>Name</th><th>Discount</th><th></th></tr>
            <?php foreach ($facilities as $f): ?>
                <tr>
                    <td><?= $f['sort_order'] ?></td>
                    <td><?= htmlspecialchars($f['name']) ?></td>
                    <td><?= htmlspecialchars($f['discount_text']) ?></td>
                    <td>
                        <form class="inline" method="POST" onsubmit="return confirm('Delete this entry?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <button type="submit" style="background:#b91c1c; padding:4px 10px; width:auto;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h1 style="margin-top:32px;">Add Facility</h1>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required placeholder="e.g. KFC">
            <label for="discount_text">Discount</label>
            <input type="text" id="discount_text" name="discount_text" placeholder="e.g. 30%">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="0">
            <button type="submit">Add</button>
        </form>

        <p style="font-size:0.8rem; color:#6b7280; margin-top:16px;">
            Note: brand logos (KFC, Subway, etc.) are trademarked. This app shows names as text only.
            If your mess has permission to use specific partner logos, you can extend
            <code>facilities.logo_path</code> and render the image directly in
            <code>includes/CardRenderer.php</code>.
        </p>
    </div>
</body>
</html>
