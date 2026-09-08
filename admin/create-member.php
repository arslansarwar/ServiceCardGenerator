<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../includes/Member.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add-member.php');
    exit;
}

$data = [
    'full_name' => $_POST['full_name'] ?? '',
    'parent_spouse_name' => $_POST['parent_spouse_name'] ?? '',
    'cnic_no' => $_POST['cnic_no'] ?? '',
    'category_id' => $_POST['category_id'] ?? '',
    'address' => $_POST['address'] ?? '',
    'valid_upto' => $_POST['valid_upto'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
];

$errors = validateMemberInput($data);

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $data;
    header('Location: add-member.php');
    exit;
}

$pdo = getDbConnection();

try {
    $photoPath = handlePhotoUpload($_FILES['photo'] ?? []);
} catch (RuntimeException $e) {
    $_SESSION['errors'] = [$e->getMessage()];
    $_SESSION['old'] = $data;
    header('Location: add-member.php');
    exit;
}

$familyMembers = $_POST['family'] ?? [];

$memberCode = createMember($pdo, $data, $photoPath, $familyMembers);

header('Location: ../public/success.php?code=' . urlencode($memberCode));
exit;
