<?php
/**
 * Security & Session Configuration
 * Kano State Polytechnic - Academic Transcript Generating System
 */

return [
    'jwt_secret'           => getenv('APP_SECRET') ?: 'ksp_transcripts_super_secure_key_2026_kanopoly',
    'token_expiry_seconds' => 86400, // 24 hours
    'remember_expiry'      => 86400 * 30, // 30 days
    'max_login_attempts'   => 5,
    'lockout_duration_min' => 15,
    'csrf_token_name'      => 'csrf_token',
    'allowed_origins'      => [
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://localhost'
    ],
    'password_policy'      => [
        'min_length'       => 6,
        'require_number'   => false,
        'require_special'  => false
    ]
];
