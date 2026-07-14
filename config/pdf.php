<?php

return [
    'max_upload_kb' => (int) env('PDF_MAX_UPLOAD_KB', 102400),
    'pdfinfo_binary' => env('PDFINFO_BINARY', 'pdfinfo'),
    'pdftoppm_binary' => env('PDFTOPPM_BINARY', 'pdftoppm'),
    'optimizer_binary' => env('PDF_OPTIMIZER_BINARY'),
];
