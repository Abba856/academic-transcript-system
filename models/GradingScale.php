<?php
/**
 * Grading Scale Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class GradingScale {
    public static function format(array $row): array {
        return [
            'id'                => (int)$row['id'],
            'letter_grade'      => $row['letter_grade'],
            'grade'             => $row['letter_grade'],
            'min_score'         => (float)$row['min_score'],
            'minScore'          => (float)$row['min_score'],
            'max_score'         => (float)$row['max_score'],
            'maxScore'          => (float)$row['max_score'],
            'grade_point'       => (float)$row['grade_point'],
            'point'             => (float)$row['grade_point'],
            'grade_description' => $row['grade_description'],
            'description'       => $row['grade_description'],
            'is_active'         => (bool)$row['is_active'],
            'display_order'     => (int)($row['display_order'] ?? 0)
        ];
    }

    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM `grading_system` WHERE is_active = 1 ORDER BY min_score DESC");
        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function getGradeForScore(float $score): array {
        $scale = self::all();
        foreach ($scale as $item) {
            if ($score >= $item['min_score'] && $score <= $item['max_score']) {
                return [
                    'grade'       => $item['grade'],
                    'grade_point' => $item['point'],
                    'description' => $item['description']
                ];
            }
        }
        return [
            'grade'       => 'F',
            'grade_point' => 0.00,
            'description' => 'Fail'
        ];
    }

    public static function getClassification(float $cgpa): string {
        if ($cgpa >= 3.50) return 'Distinction';
        if ($cgpa >= 3.00) return 'Upper Credit';
        if ($cgpa >= 2.50) return 'Lower Credit';
        if ($cgpa >= 2.00) return 'Pass';
        return 'Fail / Unsatisfactory';
    }

    public static function updateScale(array $items): bool {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $db->exec("TRUNCATE TABLE `grading_system`");
            $stmt = $db->prepare("
                INSERT INTO `grading_system` (`letter_grade`, `min_score`, `max_score`, `grade_point`, `grade_description`, `is_active`, `display_order`)
                VALUES (:grade, :min_s, :max_s, :gp, :desc, 1, :ord)
            ");
            $order = 1;
            foreach ($items as $item) {
                $stmt->execute([
                    ':grade' => $item['grade'] ?? $item['letter_grade'],
                    ':min_s' => $item['minScore'] ?? $item['min_score'],
                    ':max_s' => $item['maxScore'] ?? $item['max_score'],
                    ':gp'    => $item['point'] ?? $item['grade_point'],
                    ':desc'  => $item['description'] ?? $item['grade_description'] ?? '',
                    ':ord'   => $order++
                ]);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }
}
