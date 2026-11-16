<?php
/**
 * GPA Calculation Service
 * Computes semester GPA using courses and grades based on dynamic grading scale.
 */

namespace Services;

use Models\GradingScale;

class GPAService {
    /**
     * Calculate GPA for a given list of course results
     *
     * @param array $courses Array of courses/results with credit_unit and score / grade_point
     * @return array ['total_units' => int, 'total_points' => float, 'gpa' => float]
     */
    public static function calculate(array $courses): array {
        $totalUnits = 0;
        $totalQualityPoints = 0.0;

        foreach ($courses as $c) {
            $unit = (int)($c['credit_unit'] ?? $c['creditUnit'] ?? $c['unit'] ?? 0);
            if ($unit <= 0) continue;

            $gradePoint = null;
            if (isset($c['grade_point']) || isset($c['gradePoint']) || isset($c['point'])) {
                $gradePoint = (float)($c['grade_point'] ?? $c['gradePoint'] ?? $c['point']);
            } elseif (isset($c['total_score']) || isset($c['score'])) {
                $score = (float)($c['total_score'] ?? $c['score']);
                $gradeInfo = GradingScale::getGradeForScore($score);
                $gradePoint = $gradeInfo['grade_point'];
            } else {
                $gradePoint = 0.0;
            }

            $qp = round($unit * $gradePoint, 2);
            $totalUnits += $unit;
            $totalQualityPoints += $qp;
        }

        $gpa = $totalUnits > 0 ? round($totalQualityPoints / $totalUnits, 2) : 0.0;

        return [
            'total_units'          => $totalUnits,
            'total_quality_points' => round($totalQualityPoints, 2),
            'gpa'                  => $gpa
        ];
    }
}
