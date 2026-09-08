<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../includes/Category.php';

$pdo = getDbConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color_hex'] ?? '#1c3b2e';
        $layout = $_POST['back_layout'] ?? 'family';
        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            createCategory($pdo, $name, $color, $layout);
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        deleteCategory($pdo, (int) $_POST['id']);
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        updateCategory($pdo, (int) $_POST['id'], trim($_POST['name']), $_POST['color_hex'], $_POST['back_layout']);
    }
}

$categories = getAllCategories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 0.85rem; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .swatch { display: inline-block; width: 18px; height: 18px; border-radius: 4px; border: 1px solid #d1d5db; vertical-align: middle; }
        .nav a { margin-right: 16px; font-weight: 600; color: #4f46e5; text-decoration: none; }
        form.inline { display: inline; }
    </style>
</head>
<body>
    <div class="card-box" style="max-width: 800px;">
        <div class="nav"><a href="index.php">← Dashboard</a></div>
        <h1>Categories</h1>
        <?php if ($error): ?><div class="errors"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <table>
            <tr><th>Name</th><th>Color</th><th>Back Layout</th><th></th></tr>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td><span class="swatch" style="background:<?= htmlspecialchars($cat['color_hex']) ?>"></span> <?= htmlspecialchars($cat['color_hex']) ?></td>
                    <td><?= htmlspecialchars($cat['back_layout']) ?></td>
                    <td>
                        <form class="inline" method="POST" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" style="background:#b91c1c; padding:4px 10px; width:auto;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h1 style="margin-top:32px;">Add Category</h1>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required placeholder="e.g. Life Member">

            <label for="color_hex">Color</label>
            <input type="color" id="color_hex" name="color_hex" value="#1c3b2e" style="height:42px; padding:4px;">

            <label for="back_layout">Back-of-card layout</label>
            <select id="back_layout" name="back_layout">
                <option value="family">Family details table</option>
                <option value="facilities">Instructions + facilities/discounts</option>
            </select>

            <button type="submit">Add Category</button>
        </form>
    </div>
</body>
</html>
