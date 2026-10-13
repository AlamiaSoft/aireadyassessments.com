<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class DocumentTextExtractor
{
    public function extract(string $path, string $extension, int $maxCharacters): string
    {
        $text = match (strtolower($extension)) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            default => throw new RuntimeException('UNSUPPORTED_DOCUMENT_FORMAT'),
        };

        $text = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        if (mb_strlen($text) < 80) {
            throw new RuntimeException('DOCUMENT_TEXT_TOO_SHORT_OR_UNREADABLE');
        }

        return mb_substr($text, 0, $maxCharacters);
    }

    private function extractPdf(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('DOCUMENT_NOT_ACCESSIBLE');
        }

        $process = new Process(['pdftotext', '-layout', '-enc', 'UTF-8', $path, '-']);
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            // Do not propagate subprocess stderr; it may contain file paths or sensitive text.
            throw new RuntimeException('PDF_TEXT_EXTRACTION_FAILED');
        }

        return $process->getOutput();
    }

    private function extractDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('DOCX_ARCHIVE_INVALID');
        }

        try {
            $entry = $zip->statName('word/document.xml');
            if (! is_array($entry) || (int) ($entry['size'] ?? 0) > 25 * 1024 * 1024) {
                throw new RuntimeException('DOCX_DOCUMENT_XML_INVALID_OR_TOO_LARGE');
            }

            $xml = $zip->getFromName('word/document.xml');
            if (! is_string($xml) || strlen($xml) > 25 * 1024 * 1024) {
                throw new RuntimeException('DOCX_DOCUMENT_XML_INVALID_OR_TOO_LARGE');
            }
        } finally {
            $zip->close();
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            if (! $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new RuntimeException('DOCX_XML_PARSE_FAILED');
            }

            $xpath = new DOMXPath($dom);
            $nodes = $xpath->query('//*[local-name()="t" or local-name()="tab" or local-name()="br" or local-name()="p"]');
            if ($nodes === false) {
                throw new RuntimeException('DOCX_XML_PARSE_FAILED');
            }

            $text = '';
            foreach ($nodes as $node) {
                $name = $node->localName;
                $text .= match ($name) {
                    't' => $node->nodeValue ?? '',
                    'tab' => "\t",
                    'br', 'p' => "\n",
                    default => '',
                };
                if (strlen($text) > 2 * 1024 * 1024) {
                    break;
                }
            }

            return $text;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
