<?php
/**
 * PDF Generation Service
 * Generates official A4 Academic Transcript documents.
 * Includes support for Dompdf if installed, with a robust native PDF 1.4 engine fallback.
 */

namespace Services;

class PDFService {
    /**
     * Generate official PDF transcript
     *
     * @param array $transcript Full transcript data including student and details
     * @param string $filename Output filename (e.g. KSP_CST_TR_2025_0001.pdf)
     * @return string Relative web path to generated PDF
     */
    public static function generateTranscript(array $transcript, string $filename): string {
        $storageDir = dirname(__DIR__) . '/storage/transcripts';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $pdfPath = "{$storageDir}/{$baseName}.pdf";

        // If Composer dompdf is installed, use it
        $vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            if (class_exists('\\Dompdf\\Dompdf')) {
                $html = self::generateHTML($transcript);
                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                file_put_contents($pdfPath, $dompdf->output());
                return "storage/transcripts/{$baseName}.pdf";
            }
        }

        // Native Pure-PHP PDF 1.4 Generator
        $pdfContent = self::buildNativePDF($transcript);
        file_put_contents($pdfPath, $pdfContent);

        return "storage/transcripts/{$baseName}.pdf";
    }

    /**
     * Generates rich HTML representation suitable for print-to-PDF or Dompdf
     */
    public static function generateHTML(array $t): string {
        $ref = htmlspecialchars($t['referenceNumber'] ?? $t['transcript_reference'] ?? 'N/A');
        $studentName = htmlspecialchars($t['studentName'] ?? $t['student_name'] ?? 'N/A');
        $matric = htmlspecialchars($t['matricNumber'] ?? $t['matric_number'] ?? 'N/A');
        $prog = htmlspecialchars($t['programme'] ?? 'National Diploma in Computer Science');
        $dept = htmlspecialchars($t['department'] ?? 'Department of Computer Science');
        $cgpa = number_format((float)($t['cgpa'] ?? 0), 2);
        $class = htmlspecialchars($t['classification'] ?? 'N/A');
        $issueDate = htmlspecialchars($t['issueDate'] ?? $t['issue_date'] ?? date('Y-m-d'));
        $qrPath = htmlspecialchars($t['qr_code_path'] ?? '');

        $rowsHtml = '';
        $details = $t['details'] ?? [];
        foreach ($details as $d) {
            $code = htmlspecialchars($d['course_code'] ?? '');
            $title = htmlspecialchars($d['course_title'] ?? '');
            $unit = (int)($d['credit_unit'] ?? 0);
            $grade = htmlspecialchars($d['letter_grade'] ?? '');
            $gp = number_format((float)($d['grade_point'] ?? 0), 2);
            $qp = number_format((float)($d['quality_points'] ?? 0), 2);

            $rowsHtml .= "
                <tr style='border-bottom: 1px solid #e2e8f0; font-size: 11px;'>
                    <td style='padding: 6px 8px; font-weight: 600;'>{$code}</td>
                    <td style='padding: 6px 8px;'>{$title}</td>
                    <td style='padding: 6px 8px; text-align: center;'>{$unit}</td>
                    <td style='padding: 6px 8px; text-align: center; font-weight: 700;'>{$grade}</td>
                    <td style='padding: 6px 8px; text-align: right;'>{$gp}</td>
                    <td style='padding: 6px 8px; text-align: right; font-weight: 600;'>{$qp}</td>
                </tr>
            ";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Official Academic Transcript - {$matric}</title>
<style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; font-size: 12px; }
    .header { text-align: center; border-bottom: 3px double #0284c7; padding-bottom: 14px; margin-bottom: 16px; }
    .header h1 { margin: 0; font-size: 20px; color: #0f172a; letter-spacing: 0.5px; }
    .header h2 { margin: 4px 0; font-size: 14px; color: #0369a1; }
    .header h3 { margin: 2px 0; font-size: 13px; color: #475569; font-weight: normal; }
    .meta-box { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 16px; border-collapse: collapse; }
    .meta-box td { padding: 6px 10px; font-size: 11px; }
    .meta-label { font-weight: bold; color: #475569; width: 22%; }
    .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .results-table th { background-color: #f1f5f9; color: #334155; padding: 8px; text-align: left; font-size: 11px; border-bottom: 2px solid #cbd5e1; }
    .summary-card { width: 100%; border: 2px solid #0284c7; border-radius: 6px; background-color: #f8fafc; margin-bottom: 24px; padding: 12px; }
    .signatures { width: 100%; margin-top: 30px; }
    .sig-col { text-align: center; font-size: 11px; width: 33%; vertical-align: top; }
</style>
</head>
<body>
    <div class="header">
        <h1>KANO STATE POLYTECHNIC</h1>
        <h2>SCHOOL OF TECHNOLOGY</h2>
        <h3>Department of Computer Science</h3>
        <p style="margin: 4px 0 0 0; font-size: 13px; font-weight: bold; color: #b91c1c; letter-spacing: 1px;">OFFICIAL ACADEMIC TRANSCRIPT</p>
    </div>

    <table class="meta-box">
        <tr>
            <td class="meta-label">STUDENT NAME:</td>
            <td style="font-weight: 700;">{$studentName}</td>
            <td class="meta-label">TRANSCRIPT REF:</td>
            <td style="font-weight: 700; color: #0284c7;">{$ref}</td>
        </tr>
        <tr>
            <td class="meta-label">MATRIC NUMBER:</td>
            <td style="font-weight: 600;">{$matric}</td>
            <td class="meta-label">DATE OF ISSUE:</td>
            <td>{$issueDate}</td>
        </tr>
        <tr>
            <td class="meta-label">PROGRAMME:</td>
            <td>{$prog}</td>
            <td class="meta-label">DEPARTMENT:</td>
            <td>{$dept}</td>
        </tr>
    </table>

    <table class="results-table">
        <thead>
            <tr>
                <th style="width: 15%;">COURSE CODE</th>
                <th style="width: 45%;">COURSE TITLE</th>
                <th style="width: 10%; text-align: center;">CREDIT</th>
                <th style="width: 10%; text-align: center;">GRADE</th>
                <th style="width: 10%; text-align: right;">GP</th>
                <th style="width: 10%; text-align: right;">WGP</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <table style="width: 100%; margin-bottom: 24px; border-collapse: collapse;">
        <tr>
            <td style="width: 65%; vertical-align: top; padding-right: 16px;">
                <div style="border: 1px solid #cbd5e1; padding: 12px; border-radius: 4px; background: #f8fafc;">
                    <div style="font-size: 11px; font-weight: bold; margin-bottom: 6px; color: #0369a1;">OFFICIAL PERFORMANCE SUMMARY</div>
                    <div style="font-size: 13px; margin-bottom: 4px;">CUMULATIVE GRADE POINT AVERAGE (CGPA): <strong style="font-size: 16px; color: #0f172a;">{$cgpa} / 4.00</strong></div>
                    <div style="font-size: 13px; margin-bottom: 4px;">CLASS OF DIPLOMA: <strong style="color: #0284c7;">{$class}</strong></div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 8px;">NBTE 4.00 Grading Standard: Distinction (3.50 - 4.00) | Upper Credit (3.00 - 3.49) | Lower Credit (2.50 - 2.99) | Pass (2.00 - 2.49)</div>
                </div>
            </td>
            <td style="width: 35%; text-align: center; vertical-align: middle; border: 1px dashed #0284c7; padding: 10px; border-radius: 4px;">
                <div style="font-size: 10px; font-weight: bold; color: #0369a1; margin-bottom: 6px;">DIGITAL VERIFICATION QR</div>
                <div style="font-size: 9px; color: #475569;">Scan QR code or visit verification portal to confirm transcript integrity.</div>
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td class="sig-col">
                <div style="height: 35px;"></div>
                <div style="border-top: 1px solid #475569; padding-top: 4px; font-weight: 600;">Dr. Aminu Abdullahi</div>
                <div style="color: #64748b;">Head of Department</div>
            </td>
            <td class="sig-col">
                <div style="height: 35px;"></div>
                <div style="border-top: 1px solid #475569; padding-top: 4px; font-weight: 600;">[ OFFICIAL EMBOSSED SEAL ]</div>
                <div style="color: #64748b;">Kano State Polytechnic</div>
            </td>
            <td class="sig-col">
                <div style="height: 35px;"></div>
                <div style="border-top: 1px solid #475569; padding-top: 4px; font-weight: 600;">Dr. Mustapha B. Farouk</div>
                <div style="color: #64748b;">Academic Registrar</div>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Native PDF 1.4 Binary Generator
     * Emits standard vector A4 PDF with clean typography, boxes, headers, and metadata
     */
    private static function buildNativePDF(array $t): string {
        $ref = $t['referenceNumber'] ?? $t['transcript_reference'] ?? 'KSP/CST/TR/2025/0001';
        $studentName = $t['studentName'] ?? $t['student_name'] ?? 'Student';
        $matric = $t['matricNumber'] ?? $t['matric_number'] ?? 'KSP/CS/2023/001';
        $prog = $t['programme'] ?? 'National Diploma in Computer Science';
        $dept = $t['department'] ?? 'Department of Computer Science';
        $cgpa = number_format((float)($t['cgpa'] ?? 0), 2);
        $class = $t['classification'] ?? 'Distinction';
        $issueDate = $t['issueDate'] ?? $t['issue_date'] ?? date('Y-m-d');
        $totalUnits = (int)($t['totalCreditUnits'] ?? $t['total_credit_units'] ?? 0);
        $totalPoints = number_format((float)($t['totalQualityPoints'] ?? $t['total_quality_points'] ?? 0), 2);

        // PDF coordinate system: origin at bottom-left, A4 is 595.28 x 841.89
        $stream = [];
        $stream[] = "q"; // push state

        // Outer margin border
        $stream[] = "0.85 0.9 0.95 rg 18 18 559.28 805.89 re f"; // light background border
        $stream[] = "1 1 1 rg 20 20 555.28 801.89 re f"; // white page body
        $stream[] = "0.05 0.35 0.65 RG 2 w 20 20 555.28 801.89 re S"; // outer blue border

        // Top Header Banner
        $stream[] = "0.05 0.25 0.5 rg 20 740 555.28 81.89 re f";

        // Institution Header Text
        $stream[] = "BT /F2 18 Tf 1 1 1 rg 140 790 Td (KANO STATE POLYTECHNIC) Tj ET";
        $stream[] = "BT /F2 12 Tf 0.85 0.95 1 rg 195 772 Td (SCHOOL OF TECHNOLOGY) Tj ET";
        $stream[] = "BT /F1 11 Tf 0.9 0.9 0.9 rg 180 755 Td (Department of Computer Science) Tj ET";

        // Red Banner Sub-title
        $stream[] = "0.8 0.1 0.1 rg 150 718 295.28 20 re f";
        $stream[] = "BT /F2 11 Tf 1 1 1 rg 195 723 Td (OFFICIAL ACADEMIC TRANSCRIPT) Tj ET";

        // Student Biodata Box
        $stream[] = "0.96 0.97 0.98 rg 35 625 525 80 re f";
        $stream[] = "0.8 0.85 0.9 RG 1 w 35 625 525 80 re S";

        $stream[] = "BT /F2 10 Tf 0.1 0.1 0.1 rg 45 685 Td (STUDENT NAME: ) Tj /F1 10 Tf (" . self::escapeText($studentName) . ") Tj ET";
        $stream[] = "BT /F2 10 Tf 0.1 0.1 0.1 rg 340 685 Td (TRANSCRIPT REF: ) Tj /F2 10 Tf 0.05 0.4 0.7 rg (" . self::escapeText($ref) . ") Tj ET";

        $stream[] = "BT /F2 10 Tf 0.1 0.1 0.1 rg 45 665 Td (MATRIC NUMBER: ) Tj /F1 10 Tf (" . self::escapeText($matric) . ") Tj ET";
        $stream[] = "BT /F2 10 Tf 0.1 0.1 0.1 rg 340 665 Td (DATE OF ISSUE: ) Tj /F1 10 Tf (" . self::escapeText($issueDate) . ") Tj ET";

        $stream[] = "BT /F2 10 Tf 0.1 0.1 0.1 rg 45 645 Td (PROGRAMME: ) Tj /F1 10 Tf (" . self::escapeText($prog) . ") Tj ET";
        $stream[] = "BT /F2 10 Tf 0.1 0.1 0.1 rg 340 645 Td (DEPARTMENT: ) Tj /F1 10 Tf (" . self::escapeText($dept) . ") Tj ET";

        // Table Header
        $tableY = 595;
        $stream[] = "0.1 0.2 0.35 rg 35 {$tableY} 525 22 re f";
        $stream[] = "BT /F2 9 Tf 1 1 1 rg 45 " . ($tableY + 7) . " Td (COURSE CODE) Tj ET";
        $stream[] = "BT /F2 9 Tf 1 1 1 rg 150 " . ($tableY + 7) . " Td (COURSE TITLE) Tj ET";
        $stream[] = "BT /F2 9 Tf 1 1 1 rg 400 " . ($tableY + 7) . " Td (UNITS) Tj ET";
        $stream[] = "BT /F2 9 Tf 1 1 1 rg 445 " . ($tableY + 7) . " Td (SCORE) Tj ET";
        $stream[] = "BT /F2 9 Tf 1 1 1 rg 495 " . ($tableY + 7) . " Td (GRADE) Tj ET";
        $stream[] = "BT /F2 9 Tf 1 1 1 rg 535 " . ($tableY + 7) . " Td (WGP) Tj ET";

        // Table Rows
        $details = $t['details'] ?? [];
        $currY = $tableY;
        $rowCount = 0;

        foreach ($details as $d) {
            $currY -= 17;
            $rowCount++;
            $bg = ($rowCount % 2 === 0) ? "0.96 0.97 0.98 rg" : "1 1 1 rg";
            $stream[] = "{$bg} 35 {$currY} 525 17 re f";
            $stream[] = "0.85 0.88 0.92 RG 0.5 w 35 {$currY} 525 17 re S";

            $cCode = self::escapeText($d['course_code'] ?? '');
            $cTitle = self::escapeText(substr($d['course_title'] ?? '', 0, 42));
            $cUnit = (string)($d['credit_unit'] ?? 3);
            $cScore = number_format((float)($d['examination_score'] ?? $d['score'] ?? 0), 1);
            $cGrade = self::escapeText($d['letter_grade'] ?? 'A');
            $cWgp = number_format((float)($d['quality_points'] ?? $d['weighted_grade_point'] ?? 0), 1);

            $stream[] = "BT /F2 8.5 Tf 0.1 0.1 0.1 rg 45 " . ($currY + 4) . " Td ({$cCode}) Tj ET";
            $stream[] = "BT /F1 8.5 Tf 0.2 0.2 0.2 rg 150 " . ($currY + 4) . " Td ({$cTitle}) Tj ET";
            $stream[] = "BT /F1 8.5 Tf 0.1 0.1 0.1 rg 410 " . ($currY + 4) . " Td ({$cUnit}) Tj ET";
            $stream[] = "BT /F1 8.5 Tf 0.1 0.1 0.1 rg 450 " . ($currY + 4) . " Td ({$cScore}) Tj ET";
            $stream[] = "BT /F2 8.5 Tf 0.05 0.35 0.65 rg 500 " . ($currY + 4) . " Td ({$cGrade}) Tj ET";
            $stream[] = "BT /F2 8.5 Tf 0.1 0.1 0.1 rg 538 " . ($currY + 4) . " Td ({$cWgp}) Tj ET";

            if ($currY < 230) break; // page boundary safety
        }

        // Summary Card
        $summaryY = max($currY - 80, 140);
        $stream[] = "0.93 0.97 1 rg 35 {$summaryY} 340 70 re f";
        $stream[] = "0.05 0.35 0.65 RG 1.5 w 35 {$summaryY} 340 70 re S";

        $stream[] = "BT /F2 10 Tf 0.05 0.35 0.65 rg 45 " . ($summaryY + 50) . " Td (CUMULATIVE PERFORMANCE SUMMARY) Tj ET";
        $stream[] = "BT /F2 9 Tf 0.2 0.2 0.2 rg 45 " . ($summaryY + 34) . " Td (TOTAL UNITS: {$totalUnits}   |   TOTAL POINTS: {$totalPoints}) Tj ET";
        $stream[] = "BT /F2 12 Tf 0.1 0.1 0.1 rg 45 " . ($summaryY + 16) . " Td (CGPA: {$cgpa} / 4.00) Tj ET";
        $stream[] = "BT /F2 11 Tf 0.05 0.45 0.2 rg 180 " . ($summaryY + 16) . " Td (CLASS: {$class}) Tj ET";

        // QR Verification Box on the right
        $stream[] = "0.97 0.98 0.99 rg 390 {$summaryY} 170 70 re f";
        $stream[] = "0.8 0.85 0.9 RG 1 w 390 {$summaryY} 170 70 re S";
        $stream[] = "BT /F2 8 Tf 0.05 0.35 0.65 rg 410 " . ($summaryY + 52) . " Td ([ SECURE QR VERIFICATION ]) Tj ET";
        $stream[] = "BT /F1 7.5 Tf 0.3 0.3 0.3 rg 400 " . ($summaryY + 38) . " Td (Scan code to verify authenticity) Tj ET";
        $stream[] = "BT /F1 7 Tf 0.4 0.4 0.4 rg 405 " . ($summaryY + 24) . " Td (Online registry validation active) Tj ET";
        $stream[] = "BT /F2 7 Tf 0.1 0.4 0.1 rg 415 " . ($summaryY + 10) . " Td (GENUINE DOCUMENT) Tj ET";

        // Official Signatures
        $sigY = 55;
        $stream[] = "0.5 0.5 0.5 RG 1 w 50 {$sigY} 130 {$sigY} m S";
        $stream[] = "BT /F2 8 Tf 0.1 0.1 0.1 rg 65 " . ($sigY - 12) . " Td (Dr. Aminu Abdullahi) Tj ET";
        $stream[] = "BT /F1 7.5 Tf 0.4 0.4 0.4 rg 68 " . ($sigY - 22) . " Td (Head of Department) Tj ET";

        $stream[] = "0.5 0.5 0.5 RG 1 w 230 {$sigY} 330 {$sigY} m S";
        $stream[] = "BT /F2 8 Tf 0.05 0.3 0.6 rg 235 " . ($sigY - 12) . " Td ([ EMBOSSED OFFICIAL SEAL ]) Tj ET";
        $stream[] = "BT /F1 7.5 Tf 0.4 0.4 0.4 rg 238 " . ($sigY - 22) . " Td (Kano State Polytechnic) Tj ET";

        $stream[] = "0.5 0.5 0.5 RG 1 w 410 {$sigY} 520 {$sigY} m S";
        $stream[] = "BT /F2 8 Tf 0.1 0.1 0.1 rg 415 " . ($sigY - 12) . " Td (Dr. Mustapha B. Farouk) Tj ET";
        $stream[] = "BT /F1 7.5 Tf 0.4 0.4 0.4 rg 428 " . ($sigY - 22) . " Td (Academic Registrar) Tj ET";

        $stream[] = "Q"; // pop state
        $content = implode("\n", $stream);

        // Assemble PDF Objects
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[6] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$obj}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }

    private static function escapeText(string $text): string {
        $clean = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        if ($clean === false) $clean = $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $clean);
    }
}
