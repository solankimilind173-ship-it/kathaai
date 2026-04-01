<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates CSV file for user import: required name/email columns, valid rows, max rows.
 */
class CsvUserImportStructure implements ValidationRule
{
    public function __construct(
        private int $maxRows = 1000
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $handle = @fopen($value->getRealPath(), 'r');
        if ($handle === false) {
            $fail('The CSV file could not be read.');

            return;
        }

        $header = fgetcsv($handle);
        if ($header === false || empty($header)) {
            fclose($handle);
            $fail('The CSV file must have a header row.');

            return;
        }

        $headerLower = array_map('strtolower', array_map('trim', $header));
        $nameIdx = array_search('name', $headerLower, true);
        $emailIdx = array_search('email', $headerLower, true);

        if ($nameIdx === false || $emailIdx === false) {
            fclose($handle);
            $fail('The CSV must contain both "name" and "email" columns (case-insensitive).');

            return;
        }

        $rowNum = 1;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false && $rowNum <= $this->maxRows) {
            $rowNum++;
            $name = isset($row[$nameIdx]) ? trim(strip_tags((string) $row[$nameIdx])) : '';
            $email = isset($row[$emailIdx]) ? trim(strip_tags((string) $row[$emailIdx])) : '';

            if ($email === '') {
                $errors[] = "Row {$rowNum}: email is required.";

                continue;
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: invalid email.";

                continue;
            }
            if (strlen($name) > 255) {
                $errors[] = "Row {$rowNum}: name must not exceed 255 characters.";
            }
        }

        fclose($handle);

        if ($rowNum > $this->maxRows) {
            $errors[] = "Maximum {$this->maxRows} rows allowed.";
        }

        if ($errors !== []) {
            $fail(implode(' ', array_slice($errors, 0, 5)).(count($errors) > 5 ? ' …' : ''));
        }
    }
}
