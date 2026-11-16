<?php
/**
 * System Settings Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class Setting {
    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM `system_settings`");
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public static function get(string $key, $default = null): ?string {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = :k LIMIT 1");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public static function set(string $key, string $value, string $group = 'general'): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`)
            VALUES (:k, :v, :g)
            ON DUPLICATE KEY UPDATE `setting_value` = :v2, `setting_group` = :g2
        ");
        return $stmt->execute([
            ':k'  => $key,
            ':v'  => $value,
            ':g'  => $group,
            ':v2' => $value,
            ':g2' => $group
        ]);
    }

    public static function getStructuredSettings(): array {
        $all = self::all();
        $grading = GradingScale::all();

        return [
            'institution' => [
                'name'         => $all['institution_name'] ?? 'KANO STATE POLYTECHNIC',
                'subtitle'     => $all['faculty_name'] ?? 'School of Technology',
                'department'   => $all['department_name'] ?? 'Department of Computer Science',
                'address'      => $all['institution_address'] ?? 'P.M.B. 3401, BUK Road, Kano, Nigeria',
                'motto'        => 'Skill for Self Reliance',
                'rector'       => $all['rector_name'] ?? 'Prof. Kabir Bello Dungurawa',
                'registrar'    => $all['registrar_name'] ?? 'Dr. Mustapha B. Farouk',
                'hod'          => $all['hod_name'] ?? 'Dr. Aminu Abdullahi',
                'dean'         => 'Engr. Bashir Garba',
                'contactEmail' => 'info@kanopoly.edu.ng',
                'contactPhone' => '+234 803 123 4567',
                'website'      => 'www.kanopoly.edu.ng',
                'logo'         => $all['institution_logo'] ?? 'assets/images/logo.png',
                'seal'         => $all['institution_seal'] ?? 'assets/images/seal.svg',
            ],
            'gradingScale' => $grading,
            'userProfile'  => [
                'name'       => 'Dr. Aminu Abdullahi',
                'email'      => 'cs.hod@kanopoly.edu.ng',
                'role'       => 'Department Admin',
                'title'      => 'Head of Department',
                'department' => 'Computer Science'
            ]
        ];
    }
}
