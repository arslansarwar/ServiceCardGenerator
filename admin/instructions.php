<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' && trim($_POST['text'] ?? '') !== '') {
        $stmt = $pdo->prepare("INSERT INTO instructions (text, sort_order) VALUES (:text, :sort)");
        $stmt->execute(['text' => trim($_POST['text']), 'sort' => (int) ($_POST['sort_order'] ?? 0)]);
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM instructions WHERE id = :id");
        $stmt->execute(['id' => (int) $_POST['id']]);
    }
}

$instructions = $pdo->query("SELECT * FROM instructions ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Instructions</title>
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
        <h1>Instructions</h1>
        <p class="subtitle">Shown as bullet points on the back of cards using the "facilities" layout.</p>

        <table>
            <tr><th>#</th><th>Text</th><th></th></tr>
            <?php foreach ($instructions as $i): ?>
                <tr>
                    <td><?= $i['sort_order'] ?></td>
                    <td><?= htmlspecialchars($i['text']) ?></td>
                    <td>
                        <form class="inline" method="POST" onsubmit="return confirm('Delete this instruction?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $i['id'] ?>">
                            <button type="submit" style="background:#b91c1c; padding:4px 10px; width:auto;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h1 style="margin-top:32px;">Add Instruction</h1>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <label for="text">Text</label>
            <input type="text" id="text" name="text" required placeholder="e.g. Members are requested to show this card on request.">
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="0">
            <button type="submit">Add</button>
        </form>
    </div>
</body>
</html>
