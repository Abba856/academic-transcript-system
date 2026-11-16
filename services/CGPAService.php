<?php
/**
 * CGPA Calculation Service
 * Computes cumulative GPA across all semesters and assigns official classification.
 */

namespace Services;

use Models\GradingScale;
use Models\Result;

class CGPAService {
    /**
     * Compute cumulative GPA for student across all approved results
     *
     * @param int $studentId
     * @return array
     */
    public static function calculateForStudent(int $studentId): array {
        $results = Result::getByStudent($studentId);

        // Group by semester
        $semesters = [];
        $totalUnits = 0;
        $totalQualityPoints = 0.0;

        foreach ($results as $res) {
            $sess = $res['session_name'] ?: '2023/2024';
            $sem = $res['semester_name'] ?: 'First Semester';
            $key = "{$sess}_{$sem}";

            if (!isset($semesters[$key])) {
                $semesters[$key] = [
                    'session_name'  => $sess,
                    'semester_name' => $sem,
                    'level'         => $res['academic_level'],
                    'courses'       => [],
                    'total_units'   => 0,
                    'total_points'  => 0.0,
                    'gpa'           => 0.0
                ];
            }

            $semesters[$key]['courses'][] = $res;
            $semesters[$key]['total_units'] += $res['credit_unit'];
            $semesters[$key]['total_points'] += $res['quality_points'];

            $totalUnits += $res['credit_unit'];
            $totalQualityPoints += $res['quality_points'];
        }

        // Calculate individual semester GPAs
        foreach ($semesters as $k => &$semData) {
            $semData['total_points'] = round($semData['total_points'], 2);
            $semData['gpa'] = $semData['total_units'] > 0
                ? round($semData['total_points'] / $semData['total_units'], 2)
                : 0.0;
        }
        unset($semData);

        $cgpa = $totalUnits > 0 ? round($totalQualityPoints / $totalUnits, 2) : 0.0;
        $classification = GradingScale::getClassification($cgpa);

        return [
            'total_credit_units'   => $totalUnits,
            'total_quality_points' => round($totalQualityPoints, 2),
            'cgpa'                 => $cgpa,
            'classification'       => $classification,
            'semesters'            => array_values($semesters),
            'results_count'        => count($results)
        ];
    }
}
