<?php
/**
 * QR Code Generation Service
 * Standards-compliant QR Code (ISO/IEC 18004) encoder and SVG renderer.
 *
 * Supports byte mode, error correction level M and versions 1-10, which covers
 * payloads up to 213 bytes - more than enough for a verification URL. No
 * external libraries or binaries are required.
 */

namespace Services;

use InvalidArgumentException;

class QRCodeService {
    /**
     * Error-correction level M format indicator (2 bits).
     */
    private const ECC_FORMAT_BITS = 0b00;

    /**
     * Structure data for ECC level M, versions 1-10.
     * Each entry: [error-correction codewords per block, [[data codewords per block, block count], ...]]
     */
    private const VERSION_INFO = [
        1  => [10, [[16, 1]]],
        2  => [16, [[28, 1]]],
        3  => [26, [[44, 1]]],
        4  => [18, [[32, 2]]],
        5  => [24, [[43, 2]]],
        6  => [16, [[27, 4]]],
        7  => [18, [[31, 4]]],
        8  => [22, [[38, 2], [39, 2]]],
        9  => [22, [[36, 3], [37, 2]]],
        10 => [26, [[43, 4], [44, 1]]],
    ];

    /**
     * Alignment pattern centre coordinates per version.
     */
    private const ALIGNMENT_POSITIONS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Generate a QR Code for a given payload (URL or verification token)
     *
     * @param string $data The content/URL to encode
     * @param string $filename Output filename (without extension or with .svg)
     * @param int $size Pixel dimension
     * @return string Relative web path to the generated QR code file
     */
    public static function generate(string $data, string $filename, int $size = 220): string {
        $storageDir = dirname(__DIR__) . '/storage/qrcodes';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $svgPath = "{$storageDir}/{$baseName}.svg";

        $svgContent = self::generateSVG($data, $size);
        file_put_contents($svgPath, $svgContent);

        return "storage/qrcodes/{$baseName}.svg";
    }

    /**
     * Generate a clean, vector SVG QR code for the given text.
     */
    public static function generateSVG(string $text, int $size = 220): string {
        $matrix = self::matrix($text);
        $modules = count($matrix);
        $quietZone = 4;
        $extent = $modules + ($quietZone * 2);

        $svg = [];
        $svg[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="%d" height="%d" viewBox="0 0 %d %d" shape-rendering="crispEdges">',
            $size, $size, $extent, $extent
        );
        $svg[] = sprintf('<rect width="%d" height="%d" fill="#ffffff"/>', $extent, $extent);

        $path = '';
        for ($row = 0; $row < $modules; $row++) {
            for ($col = 0; $col < $modules; $col++) {
                if ($matrix[$row][$col]) {
                    $x = $col + $quietZone;
                    $y = $row + $quietZone;
                    $path .= "M{$x},{$y}h1v1h-1z";
                }
            }
        }

        $svg[] = sprintf('<path d="%s" fill="#000000"/>', $path);
        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Encode text as a QR module matrix.
     *
     * @return bool[][] Rows of true (dark) / false (light) modules, without quiet zone.
     */
    public static function matrix(string $text): array {
        $dataBits = self::encodeData($text, $version, $dataCodewordCount);

        [$ecPerBlock, $groups] = self::VERSION_INFO[$version];

        // Split data codewords into blocks
        $dataCodewords = [];
        for ($i = 0; $i < count($dataBits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $dataBits[$i + $j];
            }
            $dataCodewords[] = $byte;
        }

        $blocks = [];
        $offset = 0;
        foreach ($groups as [$blockLength, $blockCount]) {
            for ($b = 0; $b < $blockCount; $b++) {
                $blocks[] = array_slice($dataCodewords, $offset, $blockLength);
                $offset += $blockLength;
            }
        }

        // Error correction codewords for every block
        $ecBlocks = [];
        foreach ($blocks as $block) {
            $ecBlocks[] = self::reedSolomonRemainder($block, $ecPerBlock);
        }

        // Interleave data and EC codewords
        $codewords = [];
        $maxDataLength = max(array_map('count', $blocks));
        for ($i = 0; $i < $maxDataLength; $i++) {
            foreach ($blocks as $block) {
                if (isset($block[$i])) {
                    $codewords[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $ecBlock) {
                $codewords[] = $ecBlock[$i];
            }
        }

        $size = 17 + (4 * $version);
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $isFunction = array_fill(0, $size, array_fill(0, $size, false));

        // Function patterns
        for ($i = 0; $i < $size; $i++) {
            self::setFunctionModule($modules, $isFunction, 6, $i, $i % 2 === 0);
            self::setFunctionModule($modules, $isFunction, $i, 6, $i % 2 === 0);
        }
        self::drawFinderPattern($modules, $isFunction, 0, 0);
        self::drawFinderPattern($modules, $isFunction, 0, $size - 7);
        self::drawFinderPattern($modules, $isFunction, $size - 7, 0);

        $alignmentPositions = self::ALIGNMENT_POSITIONS[$version];
        $alignmentCount = count($alignmentPositions);
        for ($i = 0; $i < $alignmentCount; $i++) {
            for ($j = 0; $j < $alignmentCount; $j++) {
                if (($i === 0 && $j === 0)
                    || ($i === 0 && $j === $alignmentCount - 1)
                    || ($i === $alignmentCount - 1 && $j === 0)) {
                    continue;
                }
                self::drawAlignmentPattern(
                    $modules,
                    $isFunction,
                    $alignmentPositions[$i],
                    $alignmentPositions[$j]
                );
            }
        }

        self::drawFormatBits($modules, $isFunction, $size, 0);
        if ($version >= 7) {
            self::drawVersionBits($modules, $isFunction, $size, $version);
        }

        // Place interleaved codewords (zig-zag, two columns at a time)
        $bitStream = [];
        foreach ($codewords as $codeword) {
            for ($i = 7; $i >= 0; $i--) {
                $bitStream[] = ($codeword >> $i) & 1;
            }
        }

        $bitIndex = 0;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            $upward = (($right + 1) & 2) === 0;
            for ($vert = 0; $vert < $size; $vert++) {
                $row = $upward ? $size - 1 - $vert : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $col = $right - $j;
                    if (!$isFunction[$row][$col] && $bitIndex < count($bitStream)) {
                        $modules[$row][$col] = $bitStream[$bitIndex] === 1;
                        $bitIndex++;
                    }
                }
            }
        }

        // Evaluate all eight masks and keep the one with the lowest penalty
        $bestMatrix = null;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $modules;
            for ($row = 0; $row < $size; $row++) {
                for ($col = 0; $col < $size; $col++) {
                    if (!$isFunction[$row][$col] && self::maskApplies($mask, $row, $col)) {
                        $candidate[$row][$col] = !$candidate[$row][$col];
                    }
                }
            }
            self::drawFormatBits($candidate, $isFunction, $size, $mask);

            $penalty = self::calculatePenalty($candidate, $size);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMatrix = $candidate;
            }
        }

        return $bestMatrix;
    }

    /**
     * Build the padded data bit stream for the smallest version that fits.
     */
    private static function encodeData(string $text, ?int &$version, ?int &$dataCodewordCount): array {
        $length = strlen($text);

        $version = null;
        foreach (self::VERSION_INFO as $candidate => [$ecPerBlock, $groups]) {
            $capacity = 0;
            foreach ($groups as [$blockLength, $blockCount]) {
                $capacity += $blockLength * $blockCount;
            }
            $countBits = $candidate <= 9 ? 8 : 16;
            if ($length < (1 << $countBits) && 4 + $countBits + ($length * 8) <= $capacity * 8) {
                $version = $candidate;
                $dataCodewordCount = $capacity;
                break;
            }
        }

        if ($version === null) {
            throw new InvalidArgumentException(
                'QR payload is too large (maximum 213 bytes for error correction level M).'
            );
        }

        $bits = [];
        self::appendBits($bits, 0b0100, 4); // Byte mode
        self::appendBits($bits, $length, $version <= 9 ? 8 : 16);
        for ($i = 0; $i < $length; $i++) {
            self::appendBits($bits, ord($text[$i]), 8);
        }

        $capacityBits = $dataCodewordCount * 8;
        for ($i = 0; $i < 4 && count($bits) < $capacityBits; $i++) {
            $bits[] = 0;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $padBytes = [0xEC, 0x11];
        $padIndex = 0;
        while (count($bits) < $capacityBits) {
            self::appendBits($bits, $padBytes[$padIndex % 2], 8);
            $padIndex++;
        }

        return $bits;
    }

    private static function appendBits(array &$bits, int $value, int $length): void {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    private static function setFunctionModule(
        array &$modules,
        array &$isFunction,
        int $row,
        int $col,
        bool $dark
    ): void {
        if ($row < 0 || $col < 0 || $row >= count($modules) || $col >= count($modules)) {
            return;
        }
        $modules[$row][$col] = $dark;
        $isFunction[$row][$col] = true;
    }

    private static function drawFinderPattern(array &$modules, array &$isFunction, int $row, int $col): void {
        for ($dr = -1; $dr <= 7; $dr++) {
            for ($dc = -1; $dc <= 7; $dc++) {
                $distance = max(abs($dr - 3), abs($dc - 3));
                self::setFunctionModule(
                    $modules,
                    $isFunction,
                    $row + $dr,
                    $col + $dc,
                    $distance !== 2 && $distance !== 4
                );
            }
        }
    }

    private static function drawAlignmentPattern(
        array &$modules,
        array &$isFunction,
        int $centerRow,
        int $centerCol
    ): void {
        for ($dr = -2; $dr <= 2; $dr++) {
            for ($dc = -2; $dc <= 2; $dc++) {
                self::setFunctionModule(
                    $modules,
                    $isFunction,
                    $centerRow + $dr,
                    $centerCol + $dc,
                    max(abs($dr), abs($dc)) !== 1
                );
            }
        }
    }

    private static function drawFormatBits(array &$modules, array &$isFunction, int $size, int $mask): void {
        $data = (self::ECC_FORMAT_BITS << 3) | $mask;
        $remainder = $data;
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }
        $bits = (($data << 10) | $remainder) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) {
            self::setFunctionModule($modules, $isFunction, $i, 8, (($bits >> $i) & 1) === 1);
        }
        self::setFunctionModule($modules, $isFunction, 7, 8, (($bits >> 6) & 1) === 1);
        self::setFunctionModule($modules, $isFunction, 8, 8, (($bits >> 7) & 1) === 1);
        self::setFunctionModule($modules, $isFunction, 8, 7, (($bits >> 8) & 1) === 1);
        for ($i = 9; $i < 15; $i++) {
            self::setFunctionModule($modules, $isFunction, 8, 14 - $i, (($bits >> $i) & 1) === 1);
        }

        for ($i = 0; $i < 8; $i++) {
            self::setFunctionModule($modules, $isFunction, 8, $size - 1 - $i, (($bits >> $i) & 1) === 1);
        }
        for ($i = 8; $i < 15; $i++) {
            self::setFunctionModule($modules, $isFunction, $size - 15 + $i, 8, (($bits >> $i) & 1) === 1);
        }
        self::setFunctionModule($modules, $isFunction, $size - 8, 8, true);
    }

    private static function drawVersionBits(array &$modules, array &$isFunction, int $size, int $version): void {
        $remainder = $version;
        for ($i = 0; $i < 12; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 11) & 1) * 0x1F25);
        }
        $bits = ($version << 12) | $remainder;

        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) === 1;
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            self::setFunctionModule($modules, $isFunction, $b, $a, $dark);
            self::setFunctionModule($modules, $isFunction, $a, $b, $dark);
        }
    }

    private static function maskApplies(int $mask, int $row, int $col): bool {
        switch ($mask) {
            case 0: return (($row + $col) % 2) === 0;
            case 1: return ($row % 2) === 0;
            case 2: return ($col % 3) === 0;
            case 3: return (($row + $col) % 3) === 0;
            case 4: return ((intdiv($row, 2) + intdiv($col, 3)) % 2) === 0;
            case 5: return ((($row * $col) % 2) + (($row * $col) % 3)) === 0;
            case 6: return (((($row * $col) % 2) + (($row * $col) % 3)) % 2) === 0;
            case 7: return (((($row + $col) % 2) + (($row * $col) % 3)) % 2) === 0;
            default: return false;
        }
    }

    private static function calculatePenalty(array $matrix, int $size): int {
        $penalty = 0;

        // Rule 1: runs of five or more same-coloured modules
        for ($i = 0; $i < $size; $i++) {
            $runRow = 1;
            $runCol = 1;
            for ($j = 1; $j < $size; $j++) {
                if ($matrix[$i][$j] === $matrix[$i][$j - 1]) {
                    $runRow++;
                } else {
                    if ($runRow >= 5) $penalty += 3 + ($runRow - 5);
                    $runRow = 1;
                }
                if ($matrix[$j][$i] === $matrix[$j - 1][$i]) {
                    $runCol++;
                } else {
                    if ($runCol >= 5) $penalty += 3 + ($runCol - 5);
                    $runCol = 1;
                }
            }
            if ($runRow >= 5) $penalty += 3 + ($runRow - 5);
            if ($runCol >= 5) $penalty += 3 + ($runCol - 5);
        }

        // Rule 2: 2x2 blocks of the same colour
        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                $value = $matrix[$row][$col];
                if ($value === $matrix[$row][$col + 1]
                    && $value === $matrix[$row + 1][$col]
                    && $value === $matrix[$row + 1][$col + 1]) {
                    $penalty += 3;
                }
            }
        }

        // Rule 3: finder-like 1:1:3:1:1 patterns with four light modules on a side
        $patternA = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
        $patternB = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j <= $size - 11; $j++) {
                $rowWindow = [];
                $colWindow = [];
                for ($k = 0; $k < 11; $k++) {
                    $rowWindow[] = $matrix[$i][$j + $k] ? 1 : 0;
                    $colWindow[] = $matrix[$j + $k][$i] ? 1 : 0;
                }
                if ($rowWindow === $patternA || $rowWindow === $patternB) $penalty += 40;
                if ($colWindow === $patternA || $colWindow === $patternB) $penalty += 40;
            }
        }

        // Rule 4: balance of dark and light modules
        $dark = 0;
        foreach ($matrix as $row) {
            foreach ($row as $module) {
                if ($module) $dark++;
            }
        }
        $total = $size * $size;
        $percent = ($dark * 100) / $total;
        $penalty += ((int)floor(abs($percent - 50) / 5)) * 10;

        return $penalty;
    }

    /**
     * Compute Reed-Solomon error correction codewords over GF(256).
     *
     * @param int[] $data
     * @return int[] Error correction codewords
     */
    private static function reedSolomonRemainder(array $data, int $degree): array {
        $divisor = self::reedSolomonDivisor($degree);
        $remainder = array_fill(0, $degree, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $remainder[$i] ^= self::gfMultiply($divisor[$i], $factor);
            }
        }

        return $remainder;
    }

    /**
     * @return int[] Generator polynomial coefficients (monic, highest degree first)
     */
    private static function reedSolomonDivisor(int $degree): array {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;

        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMultiply($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::gfMultiply($root, 0x02);
        }

        return $result;
    }

    private static function gfMultiply(int $x, int $y): int {
        $product = 0;
        for ($i = 7; $i >= 0; $i--) {
            $product = ($product << 1) ^ ((($product >> 7) & 1) * 0x11D);
            $product ^= (($y >> $i) & 1) * $x;
        }
        return $product & 0xFF;
    }
}
