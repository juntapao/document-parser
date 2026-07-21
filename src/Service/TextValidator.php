<?php

namespace App\Service;

class TextValidator
{
    public function validate(string $text): array
    {
        $patterns = [
            'invoice_number' => '/Invoice\s+#\d{5}/',
            'date' => '/Date:\s+\d{4}-\d{2}-\d{2}/',
        ];

        $errors = [];
        foreach ($patterns as $label => $pattern) {
            if (!preg_match($pattern, $text)) {
                $errors[] = "Missing or invalid $label";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
