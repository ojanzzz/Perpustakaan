<?php

return [
    'max_upload_kb' => (int) env('PDF_MAX_UPLOAD_KB', 102400),
    'pdfinfo_binary' => env('PDFINFO_BINARY', 'pdfinfo'),
    'pdftoppm_binary' => env('PDFTOPPM_BINARY', 'pdftoppm'),
    'ghostscript_binary' => env('GHOSTSCRIPT_BINARY', 'gs'),
    'python_binary' => env('PYTHON_BINARY', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
    'optimizer_binary' => env('PDF_OPTIMIZER_BINARY'),
];
