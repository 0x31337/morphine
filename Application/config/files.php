<?php
// Application file operations security configuration
// Only allow file operations within these directories (relative to project root)
return [
    'ALLOWED_DIRS' => [
        '/uploads',
        '/public_files',
        // Add more as needed
    ],
]; 