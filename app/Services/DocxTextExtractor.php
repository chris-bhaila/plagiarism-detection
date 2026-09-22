<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use ZipArchive;

/**
 * Pulls the plain text out of a .docx (a zip whose body lives in
 * word/document.xml) without needing a Word library — paragraphs become
 * newline-separated lines, which is all the similarity check needs.
 */
class DocxTextExtractor
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * Returns the document's text, or null if the file isn't a readable docx.
     */
    public function extract(string $path): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return null;
        }

        $dom = new DOMDocument;

        if (! @$dom->loadXML($xml, LIBXML_NONET)) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS);

        $paragraphs = [];

        foreach ($xpath->query('//w:body//w:p') as $paragraph) {
            $line = '';

            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) as $node) {
                $line .= match ($node->localName) {
                    't' => $node->textContent,
                    'tab' => "\t",
                    default => "\n",
                };
            }

            $paragraphs[] = trim($line);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $paragraphs)));
    }
}
