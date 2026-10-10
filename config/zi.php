<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bukti dukung (evidence) storage
    |--------------------------------------------------------------------------
    |
    | Files are stored on a private disk and only served through an authorized
    | download endpoint. Make sure PHP's upload_max_filesize / post_max_size
    | are at least as large as max_kb.
    |
    */

    'evidence' => [
        'disk' => env('ZI_EVIDENCE_DISK', 'local'),
        'max_kb' => (int) env('ZI_EVIDENCE_MAX_KB', 20480),
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'jpg', 'jpeg', 'png',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification
    |--------------------------------------------------------------------------
    |
    | separation_of_duties: the uploader of an evidence (or of the version under
    | review) may not verify it.
    |
    */

    'verification' => [
        'separation_of_duties' => (bool) env('ZI_SEPARATION_OF_DUTIES', true),
    ],

    'reminders' => [
        'days_before_due' => (int) env('ZI_REMINDER_DAYS_BEFORE_DUE', 3),
    ],

];
