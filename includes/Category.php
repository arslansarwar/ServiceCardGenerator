<?php

require_once __DIR__ . '/../config/database.php';

function getAllCategories(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

function getCategoryById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

function createCategory(PDO $pdo, string $name, string $colorHex, string $backLayout): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO categories (name, color_hex, back_layout) VALUES (:name, :color, :layout)"
    );
    $stmt->execute(['name' => $name, 'color' => $colorHex, 'layout' => $backLayout]);
}

function updateCategory(PDO $pdo, int $id, string $name, string $colorHex, string $backLayout): void
{
    $stmt = $pdo->prepare(
        "UPDATE categories SET name = :name, color_hex = :color, back_layout = :layout WHERE id = :id"
    );
    $stmt->execute(['name' => $name, 'color' => $colorHex, 'layout' => $backLayout, 'id' => $id]);
}

function deleteCategory(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

function getAllInstructions(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM instructions ORDER BY sort_order ASC, id ASC")->fetchAll();
}

function getAllFacilities(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM facilities ORDER BY sort_order ASC, id ASC")->fetchAll();
}
