<?php

namespace App\Domain\Documents;

readonly class PdfProbe
{
    public function __construct(public int $pageCount) {}
}
