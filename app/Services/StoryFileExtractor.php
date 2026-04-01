<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class StoryFileExtractor
{
    public const ALLOWED_MIMES = [
        'application/pdf',
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    ];

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];

    public const MAX_FILE_SIZE_MB = 20;

    /**
     * Extract plain text from an uploaded PDF or Word (DOC/DOCX) file.
     *
     * @return string Extracted text, or empty string on failure
     */
    public function extract(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?? '');

        try {
            if ($extension === 'pdf') {
                return $this->extractFromPdf($path);
            }
            if (in_array($extension, ['doc', 'docx'], true)) {
                return $this->extractFromWord($path, $extension);
            }
        } catch (Throwable $e) {
            report($e);

            return '';
        }

        return '';
    }

    /**
     * Validate that the file is an allowed type and size.
     */
    public function validate(UploadedFile $file): array
    {
        $errors = [];

        $ext = strtolower($file->getClientOriginalExtension() ?? '');
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $errors[] = 'Only PDF and Word (.doc, .docx) files are allowed.';
        }

        $maxBytes = self::MAX_FILE_SIZE_MB * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            $errors[] = 'File must not exceed '.self::MAX_FILE_SIZE_MB.' MB.';
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            $errors[] = 'Invalid file type. Please upload a PDF or Word document.';
        }

        return $errors;
    }

    protected function extractFromPdf(string $path): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText();

        return $text !== null ? trim(preg_replace('/\s+/', ' ', $text)) : '';
    }

    protected function extractFromWord(string $path, string $extension): string
    {
        $readerName = $extension === 'docx' ? 'Word2007' : 'MsDoc';
        $reader = IOFactory::createReader($readerName);
        $phpWord = $reader->load($path);

        $parts = [];
        foreach ($phpWord->getSections() as $section) {
            $parts[] = $this->extractTextFromContainer($section);
        }

        return trim(preg_replace('/\s+/', ' ', implode("\n", array_filter($parts))));
    }

    /**
     * Recursively extract text from a container (Section, TextRun, Table cell, etc.).
     */
    protected function extractTextFromContainer(object $container): string
    {
        if (! method_exists($container, 'getElements')) {
            return '';
        }

        $parts = [];
        foreach ($container->getElements() as $element) {
            if ($element instanceof Text) {
                $parts[] = $element->getText();
            } elseif ($element instanceof TextRun) {
                $parts[] = $element->getText();
            } elseif ($element instanceof Table) {
                foreach ($element->getRows() as $row) {
                    $rowParts = [];
                    foreach ($row->getCells() as $cell) {
                        $rowParts[] = $this->extractTextFromContainer($cell);
                    }
                    $parts[] = implode(' ', $rowParts);
                }
            } else {
                // ListItem, Title, etc. – try getText or recurse
                if (method_exists($element, 'getText')) {
                    $parts[] = $element->getText();
                } elseif (method_exists($element, 'getElements')) {
                    $parts[] = $this->extractTextFromContainer($element);
                }
            }
        }

        return implode(' ', array_filter($parts));
    }
}
