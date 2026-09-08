<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Generate a unique, human-readable member code, e.g. MEM-2026-000123
 */
function generateMemberCode(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM members WHERE member_code LIKE :prefix");
    $stmt->execute(['prefix' => "MEM-{$year}-%"]);
    $count = (int) $stmt->fetch()['cnt'];

    do {
        $count++;
        $code = sprintf('MEM-%s-%06d', $year, $count);
        $check = $pdo->prepare("SELECT 1 FROM members WHERE member_code = :code");
        $check->execute(['code' => $code]);
    } while ($check->fetch());

    return $code;
}

/**
 * Validate submitted entry form data. Returns an array of error messages (empty if valid).
 */
function validateMemberInput(array $data): array
{
    $errors = [];

    if (empty(trim($data['full_name'] ?? ''))) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($data['full_name']) > 150) {
        $errors[] = 'Full name is too long.';
    }

    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address looks invalid.';
    }

    if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{6,30}$/', $data['phone'])) {
        $errors[] = 'Phone number format looks invalid.';
    }

    if (!empty($data['cnic_no']) && !preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]$/', $data['cnic_no'])) {
        $errors[] = 'CNIC must be in the format 00000-0000000-0.';
    }

    if (empty($data['category_id']) || !ctype_digit((string) $data['category_id'])) {
        $errors[] = 'Please select a category.';
    }

    return $errors;
}

/**
 * Handle the uploaded photo file. Returns the stored relative path, or null if no file was uploaded.
 * Throws a RuntimeException with a user-friendly message on failure.
 */
function handlePhotoUpload(array $file): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Photo upload failed. Please try again.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Photo must be a JPG or PNG image.');
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('Photo must be smaller than 3MB.');
    }

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = __DIR__ . '/../uploads/photos/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $destPath = $destDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Could not save uploaded photo.');
    }

    return 'uploads/photos/' . $filename; // relative path, stored in DB
}

/**
 * Insert a new member record (and optional family members) and return the generated member_code.
 */
function createMember(PDO $pdo, array $data, ?string $photoPath, array $familyMembers = []): string
{
    $memberCode = generateMemberCode($pdo);
    $joinedAt = date('Y-m-d');
    $validUpto = !empty($data['valid_upto']) ? $data['valid_upto'] : date('Y-m-d', strtotime('+1 year'));

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO members
                (member_code, full_name, parent_spouse_name, cnic_no, category_id, address, photo_path, email, phone, joined_at, valid_upto, status)
             VALUES
                (:member_code, :full_name, :parent_spouse_name, :cnic_no, :category_id, :address, :photo_path, :email, :phone, :joined_at, :valid_upto, 'active')"
        );

        $stmt->execute([
            'member_code' => $memberCode,
            'full_name' => trim($data['full_name']),
            'parent_spouse_name' => $data['parent_spouse_name'] ?? null,
            'cnic_no' => $data['cnic_no'] ?? null,
            'category_id' => (int) $data['category_id'],
            'address' => $data['address'] ?? null,
            'photo_path' => $photoPath,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $joinedAt,
            'valid_upto' => $validUpto,
        ]);

        $memberId = (int) $pdo->lastInsertId();

        if (!empty($familyMembers)) {
            $famStmt = $pdo->prepare(
                "INSERT INTO family_members (member_id, name, relationship, birth_year) VALUES (:member_id, :name, :relationship, :birth_year)"
            );
            foreach ($familyMembers as $fam) {
                if (empty(trim($fam['name'] ?? ''))) {
                    continue; // skip blank rows
                }
                $famStmt->execute([
                    'member_id' => $memberId,
                    'name' => trim($fam['name']),
                    'relationship' => trim($fam['relationship'] ?? ''),
                    'birth_year' => $fam['birth_year'] ?? null,
                ]);
            }
        }

        $pdo->commit();
        return $memberCode;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Fetch a single member by their public member_code, joined with category info.
 */
function getMemberByCode(PDO $pdo, string $memberCode): ?array
{
    $stmt = $pdo->prepare(
        "SELECT m.*, c.name AS category_name, c.color_hex, c.back_layout
         FROM members m
         JOIN categories c ON c.id = m.category_id
         WHERE m.member_code = :code LIMIT 1"
    );
    $stmt->execute(['code' => $memberCode]);
    $result = $stmt->fetch();
    return $result ?: null;
}

function getFamilyMembers(PDO $pdo, int $memberId): array
{
    $stmt = $pdo->prepare("SELECT * FROM family_members WHERE member_id = :id ORDER BY id ASC");
    $stmt->execute(['id' => $memberId]);
    return $stmt->fetchAll();
}
