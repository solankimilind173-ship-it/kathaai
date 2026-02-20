<?php

namespace Tests\Unit\Rules;

use App\Rules\CsvUserImportStructure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CsvUserImportStructureTest extends TestCase
{
    public function test_valid_csv_with_name_and_email_passes(): void
    {
        $csv = "name,email\nJohn Doe,john@example.com\nJane Smith,jane@example.com";
        $file = $this->createCsvFile($csv);
        $rule = new CsvUserImportStructure(1000);
        $validator = Validator::make(['file' => $file], ['file' => [$rule]]);
        $this->assertFalse($validator->fails());
    }

    public function test_missing_email_column_fails(): void
    {
        $csv = "name,phone\nJohn,123";
        $file = $this->createCsvFile($csv);
        $rule = new CsvUserImportStructure(1000);
        $validator = Validator::make(['file' => $file], ['file' => [$rule]]);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('name', $validator->errors()->first('file'));
        $this->assertStringContainsString('email', $validator->errors()->first('file'));
    }

    public function test_missing_name_column_fails(): void
    {
        $csv = "email,phone\njohn@example.com,123";
        $file = $this->createCsvFile($csv);
        $rule = new CsvUserImportStructure(1000);
        $validator = Validator::make(['file' => $file], ['file' => [$rule]]);
        $this->assertTrue($validator->fails());
    }

    public function test_case_insensitive_header_accepts_name_and_email(): void
    {
        $csv = "Name,Email\nJohn,john@example.com";
        $file = $this->createCsvFile($csv);
        $rule = new CsvUserImportStructure(1000);
        $validator = Validator::make(['file' => $file], ['file' => [$rule]]);
        $this->assertFalse($validator->fails());
    }

    public function test_invalid_email_in_row_fails(): void
    {
        $csv = "name,email\nJohn,not-an-email";
        $file = $this->createCsvFile($csv);
        $rule = new CsvUserImportStructure(1000);
        $validator = Validator::make(['file' => $file], ['file' => [$rule]]);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('invalid email', $validator->errors()->first('file'));
    }

    public function test_empty_email_in_row_fails(): void
    {
        $csv = "name,email\nJohn,";
        $file = $this->createCsvFile($csv);
        $rule = new CsvUserImportStructure(1000);
        $validator = Validator::make(['file' => $file], ['file' => [$rule]]);
        $this->assertTrue($validator->fails());
    }

    private function createCsvFile(string $content): UploadedFile
    {
        $path = sys_get_temp_dir() . '/test_import_' . uniqid() . '.csv';
        file_put_contents($path, $content);
        return new UploadedFile($path, 'test.csv', 'text/csv', null, true);
    }
}
