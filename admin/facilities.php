<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Uploads.php';

$pdo = getDbConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && trim($_POST['name'] ?? '') !== '') {
        try {
            $logoPath = handleImageUpload($_FILES['logo'] ?? [], 'logos');
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                "INSERT INTO facilities (name, discount_text, logo_path, sort_order) VALUES (:name, :discount, :logo, :sort)"
            );
            $stmt->execute([
                'name' => trim($_POST['name']),
                'discount' => trim($_POST['discount_text'] ?? ''),
                'logo' => $logoPath,
                'sort' => (int) ($_POST['sort_order'] ?? 0),
            ]);
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        // Look up the logo path first so we can clean up the file, not just the DB row
        $lookup = $pdo->prepare("SELECT logo_path FROM facilities WHERE id = :id");
        $lookup->execute(['id' => (int) $_POST['id']]);
        $row = $lookup->fetch();
        if ($row) {
            deleteUploadedImage($row['logo_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM facilities WHERE id = :id");
        $stmt->execute(['id' => (int) $_POST['id']]);
    } elseif ($action === 'remove_logo' && !empty($_POST['id'])) {
        $lookup = $pdo->prepare("SELECT logo_path FROM facilities WHERE id = :id");
        $lookup->execute(['id' => (int) $_POST['id']]);
        $row = $lookup->fetch();
        if ($row) {
            deleteUploadedImage($row['logo_path']);
            $stmt = $pdo->prepare("UPDATE facilities SET logo_path = NULL WHERE id = :id");
            $stmt->execute(['id' => (int) $_POST['id']]);
        }
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
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .nav a { margin-right: 16px; font-weight: 600; color: #4f46e5; text-decoration: none; }
        form.inline { display: inline; }
        .logo-thumb { width: 32px; height: 32px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 4px; background: #fff; }
        .no-logo { color: #9ca3af; font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="card-box" style="max-width: 750px;">
    <div class="nav"><a href="index.php">← Dashboard</a></div>
    <h1>Facilities & Discounts</h1>
    <p class="subtitle">
        Shown on the back of "facilities"-layout cards. Upload a logo only if your mess has
        permission to use that brand's logo — otherwise leave it blank and just the name/discount
        will show as text.
    </p>

    <?php if ($error): ?><div class="errors"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <table>
        <tr><th>#</th><th>Logo</th><th>Name</th><th>Discount</th><th></th></tr>
        <?php foreach ($facilities as $f): ?>
            <tr>
                <td><?= $f['sort_order'] ?></td>
                <td>
                    <?php if (!empty($f['logo_path']) && file_exists(__DIR__ . '/../' . $f['logo_path'])): ?>
                        <img class="logo-thumb" src="../<?= htmlspecialchars($f['logo_path']) ?>" alt="<?= htmlspecialchars($f['name']) ?> logo">
                    <?php else: ?>
                        <span class="no-logo">none</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($f['name']) ?></td>
                <td><?= htmlspecialchars($f['discount_text']) ?></td>
                <td>
                    <?php if (!empty($f['logo_path'])): ?>
                        <form class="inline" method="POST" onsubmit="return confirm('Remove this logo?');">
                            <input type="hidden" name="action" value="remove_logo">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <button type="submit" style="background:#e5e7eb; color:#1f2430; padding:4px 10px; width:auto;">Remove logo</button>
                        </form>
                    <?php endif; ?>
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
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required placeholder="e.g. KFC">
        <label for="discount_text">Discount</label>
        <input type="text" id="discount_text" name="discount_text" placeholder="e.g. 30%">
        <label for="logo">Logo (optional, JPG/PNG, max 2MB)</label>
        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="0">
        <button type="submit">Add</button>
    </form>

    <p style="font-size:0.8rem; color:#6b7280; margin-top:16px;">
        ⚠ Only upload a logo you have the rights or permission to use commercially. Brand logos
        (KFC, Subway, etc.) are trademarked — using them without authorization from the brand
        could create legal risk for your mess. When in doubt, leave the logo blank; the name and
        discount text alone are enough for members to recognize the offer.
    </p>
</div>
</body>
</html>