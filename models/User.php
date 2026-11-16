<?php
/**
 * User Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class User {
    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, r.role_name
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmailOrUsername(string $identifier): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, r.role_name
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.email = :email OR u.username = :uname
            LIMIT 1
        ");
        $stmt->execute([
            ':email' => $identifier,
            ':uname' => $identifier
        ]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT u.id, u.full_name, u.username, u.email, u.role_id, u.status, u.last_login, u.created_at, r.role_name
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll();
    }

    public static function updateLastLogin(int $id): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO `users` (`full_name`, `username`, `email`, `password`, `role_id`, `status`)
            VALUES (:full_name, :username, :email, :password, :role_id, :status)
        ");
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':username'  => $data['username'],
            ':email'     => $data['email'],
            ':password'  => $data['password'],
            ':role_id'   => $data['role_id'],
            ':status'    => $data['status'] ?? 'active'
        ]);
        return (int)$db->lastInsertId();
    }

    public static function updatePassword(int $id, string $hashedPassword): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE `users` SET `password` = :pass WHERE `id` = :id");
        return $stmt->execute([':pass' => $hashedPassword, ':id' => $id]);
    }
}
