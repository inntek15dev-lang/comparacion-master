<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contextos de Subida Segura — Escudo Criptográfico OvalControl
    |--------------------------------------------------------------------------
    | Contextos con encrypt:true → los PDFs se almacenan en AES-256-CBC
    | en disk:local (fuera del webroot), nunca accesibles via URL directa.
    |
    | Contextos sin encrypt (o encrypt:false) → comportamiento original.
    */
    'contexts' => [

        // ── ACREDITACIÓN DE EMPRESAS Y TRABAJADORES (encriptado) ──────────────
        'acreditacion' => [
            'disk'              => 'local',
            'encrypt'           => true,
            'max_size'          => 30720, // 30MB
            'allowed_mimes'     => ['pdf'],
            'allowed_mimetypes' => ['application/pdf'],
        ],

        // ── VERIFICACIÓN PERIÓDICA (encriptado) ────────────────────────────────
        'verificacion' => [
            'disk'              => 'local',
            'encrypt'           => true,
            'max_size'          => 10240, // 10MB
            'allowed_mimes'     => ['pdf'],
            'allowed_mimetypes' => ['application/pdf'],
        ],

        // ── OPERADOR IA (encriptado) ────────────────────────────────────────────
        'ia_operator' => [
            'disk'              => 'local',
            'encrypt'           => true,
            'max_size'          => 30720,
            'allowed_mimes'     => ['pdf'],
            'allowed_mimetypes' => ['application/pdf'],
        ],

        // ── IMPORTACIÓN EXCEL (sin encriptación — no son PDFs) ─────────────────
        'excel_import' => [
            'disk'              => 'local',
            'encrypt'           => false,
            'max_size'          => 10240, // 10MB
            'allowed_mimes'     => ['xlsx', 'xls'],
            'allowed_mimetypes' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
            ],
        ],

        // ── JUSTIFICATIVOS (puede contener imágenes, sin encriptación por ahora) ─
        'justificativo' => [
            'disk'              => 'public',
            'encrypt'           => false,
            'max_size'          => 5120, // 5MB
            'allowed_mimes'     => ['pdf', 'jpg', 'jpeg', 'png'],
            'allowed_mimetypes' => ['application/pdf', 'image/jpeg', 'image/png'],
        ],

        // ── POPUPS (texto/html, sin encriptación) ──────────────────────────────
        'popup' => [
            'disk'              => 'public',
            'encrypt'           => false,
            'max_size'          => 1024, // 1MB
            'allowed_mimes'     => ['txt', 'html'],
            'allowed_mimetypes' => ['text/plain', 'text/html'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklist de Extensiones Peligrosas
    |--------------------------------------------------------------------------
    */
    'blacklist' => [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'phps',
        'exe', 'bat', 'sh', 'cmd', 'com', 'msi', 'js', 'vbs', 'wsf',
        'htaccess', 'htpasswd', 'env', 'py', 'rb', 'pl', 'sh', 'bin',
    ],
];
