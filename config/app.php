<?php
/**
 * Application Configuration
 * Kano State Polytechnic - Academic Transcript Generating System
 */

date_default_timezone_set('Africa/Lagos');

return [
    'name'           => 'Academic Transcript Generating System with QR Verification',
    'short_name'     => 'KSP Transcript System',
    'institution'    => 'Kano State Polytechnic',
    'faculty'        => 'School of Technology',
    'department'     => 'Department of Computer Science',
    'version'        => '1.0.0',
    'env'            => getenv('APP_ENV') ?: 'development',
    'debug'          => getenv('APP_DEBUG') !== false ? filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) : true,
    'base_url'       => getenv('APP_URL') ?: 'http://localhost:8000',
    'storage_path'   => dirname(__DIR__) . '/storage',
    'transcripts_dir'=> dirname(__DIR__) . '/storage/transcripts',
    'qrcodes_dir'    => dirname(__DIR__) . '/storage/qrcodes',
    'uploads_dir'    => dirname(__DIR__) . '/storage/uploads',
];
