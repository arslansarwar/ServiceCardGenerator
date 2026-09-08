<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../includes/Category.php';

$pdo = getDbConnection();
$categories = getAllCategories($pdo);

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Member</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="card-box" style="max-width: 560px;">
        <p><a href="index.php" style="color:#4f46e5; font-weight:600; text-decoration:none;">← Dashboard</a></p>
        <h1>Services Officers Mess Jhelum</h1>
        <p class="subtitle">Add new member</p>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul style="margin:0; padding-left: 18px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="errors" style="background:#fffbeb; border-color:#fde68a; color:#92400e;">
                ⚠ Please re-select the photo below — browsers clear file selections when a form reloads.
            </div>
        <?php endif; ?>

        <form action="create-member.php" method="POST" enctype="multipart/form-data">
            <label for="full_name">Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required>

            <label for="parent_spouse_name">Parents / Spouse</label>
            <input type="text" id="parent_spouse_name" name="parent_spouse_name" value="<?= htmlspecialchars($old['parent_spouse_name'] ?? '') ?>">

            <label for="cnic_no">CNIC No (00000-0000000-0)</label>
            <input type="text" id="cnic_no" name="cnic_no" placeholder="38301-7887740-7" value="<?= htmlspecialchars($old['cnic_no'] ?? '') ?>">

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (($old['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($old['address'] ?? '') ?>">

            <label for="photo">Photo (JPG/PNG, max 3MB)</label>
            <input type="file" id="photo" name="photo" required accept="image/jpeg,image/png">

            <label for="valid_upto">Valid Upto</label>
            <input type="date" id="valid_upto" name="valid_upto" value="<?= htmlspecialchars($old['valid_upto'] ?? date('Y-12-31')) ?>">

            <label for="email">Email (optional)</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">

            <label for="phone">Phone (optional)</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">

            <div id="family-section" style="margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                <label style="margin-top:0;">Family Members (optional, shown on cards with a family-details back layout)</label>
                <div id="family-rows"></div>
                <button type="button" onclick="addFamilyRow()" style="background:#e5e7eb; color:#1f2430; margin-top:8px;">+ Add Family Member</button>
            </div>

            <button type="submit">Create Membership</button>
        </form>
    </div>

    <script>
        let famIndex = 0;
        function addFamilyRow() {
            const container = document.getElementById('family-rows');
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.gap = '8px';
            row.style.marginTop = '8px';
            row.innerHTML = `
                <input type="text" name="family[${famIndex}][name]" placeholder="Name" style="flex:2;">
                <input type="text" name="family[${famIndex}][relationship]" placeholder="Relationship" style="flex:1;">
                <input type="text" name="family[${famIndex}][birth_year]" placeholder="Birth Year" style="flex:1;">
            `;
            container.appendChild(row);
            famIndex++;
        }
    </script>
</body>
</html>
