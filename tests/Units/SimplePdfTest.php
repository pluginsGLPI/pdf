<?php

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

declare(strict_types=1);

namespace GlpiPlugin\Pdf\Tests\Units;

use Document;
use Glpi\Tests\DbTestCase;
use PluginPdfSimplePDF;
use Psr\Log\LogLevel;
use ReflectionMethod;

class SimplePdfTest extends DbTestCase
{
    /**
     * @var string[]
     */
    private array $files_to_remove = [];

    public function tearDown(): void
    {
        foreach ($this->files_to_remove as $file) {
            unlink($file);
        }
        $this->files_to_remove = [];

        parent::tearDown();
    }

    /**
     * Test nested quote url in a style attribute
     * used to break cleanTableHtml regex and crash TCPDF
     */
    public function testCleanTableHtmlWithNestedQuoteUrlStyle(): void
    {
        $html = <<<HTML
            <table style="width: 614px; border-collapse: collapse;" border="0" width="614">
                <tbody>
                    <tr>
                        <td style="width: 51.8pt; height: 15.75pt; background-image: url('https://example.com/pics/a.jpg');">Content</td>
                    </tr>
                </tbody>
            </table>
            HTML;

        $pdf = new PluginPdfSimplePDF();

        $method = new ReflectionMethod(PluginPdfSimplePDF::class, 'cleanTableHtml');
        $cleaned = $method->invoke($pdf, $html);

        // no duplicated attribute on <table>
        $this->assertMatchesRegularExpression('/^<table[^>]*>/', $cleaned);
        preg_match('/^<table([^>]*)>/', $cleaned, $matches);
        $tableAttributes = $matches[1];

        $this->assertSame(1, substr_count($tableAttributes, 'border='), 'the <table> tag must have a single border attribute');
        $this->assertSame(1, substr_count($tableAttributes, 'style='), 'the <table> tag must have a single style attribute');

        // width/height stripped, nested url untouched
        $this->assertStringNotContainsStringIgnoringCase('width: 614px', $cleaned);
        $this->assertStringNotContainsStringIgnoringCase('width: 51.8pt', $cleaned);
        $this->assertStringNotContainsStringIgnoringCase('height: 15.75pt', $cleaned);
        $this->assertStringContainsString("url('https://example.com/pics/a.jpg')", $cleaned);
    }

    /**
     * Test a corrupted PNG (valid header, broken data)
     * used to crash TCPDF with "imagecolorat(): Argument #1 ($image) must be of type GdImage, false given"
     */
    public function testDisplayLineDropsCorruptedPng(): void
    {
        $file = GLPI_TMP_DIR . '/' . uniqid('corrupted_', true) . '.png';
        file_put_contents($file, $this->getCorruptedPngContent());

        try {
            $pdf = new PluginPdfSimplePDF();
            $pdf->newPage();
            $pdf->setColumnsSize(100);
            $pdf->displayLine('before <img src="' . $file . '" width="10" height="10"> after');

            $this->assertStringStartsWith('%PDF-', $pdf->output());
        } finally {
            unlink($file);
        }

        $this->hasPhpLogRecordThatContains('fatal libpng error', LogLevel::WARNING);
        $this->hasPhpLogRecordThatContains('setjmp returns error condition', LogLevel::WARNING);
        $this->hasPhpLogRecordThatContains('is not a valid PNG file', LogLevel::WARNING);
    }

    /**
     * Test a KB image pointing to a document
     * the `docid` link was not found once `=` encoded as `&#61;` by the sanitizer, so the image was missing from the PDF
     */
    public function testDisplayTextEmbedsDocumentImage(): void
    {
        $docid = $this->createDocumentFile('PNG', 'png', $this->getValidPngContent());

        $pdf = new PluginPdfSimplePDF();
        $pdf->newPage();
        $pdf->displayText('Answer', $this->getDocumentImageHtml($docid));

        $this->assertStringContainsString('/Subtype /Image', $pdf->output());
    }

    /**
     * Test a KB image pointing to a document that is not an image
     */
    public function testDisplayTextDropsNonImageDocument(): void
    {
        $docid = $this->createDocumentFile('PNG', 'png', 'this is not an image');

        $pdf = new PluginPdfSimplePDF();
        $pdf->newPage();
        $pdf->displayText('Answer', $this->getDocumentImageHtml($docid));

        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringNotContainsString('/Subtype /Image', $output);
    }

    /**
     * Test a base64 image in a rich text (e.g. a ticket task)
     * `+` and `=` were encoded as `&#43;` and `&#61;` by the sanitizer, so the image data was corrupted and TCPDF crashed
     */
    public function testDisplayTextEmbedsBase64Image(): void
    {
        // 1x1 semi-transparent PNG, its base64 contains both `+` and `=`
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADUlEQVQImWP4z8BQBwAEfwF+fk6erwAAAABJRU5ErkJggg==';

        $pdf = new PluginPdfSimplePDF();
        $pdf->newPage();
        $pdf->displayText('Description', '<p>before <img src="data:image/png;base64,' . $base64 . '" width="10"> after</p>');

        $this->assertStringContainsString('/Subtype /Image', $pdf->output());
    }

    /**
     * Test a corrupted base64 image in a rich text
     */
    public function testDisplayTextDropsCorruptedBase64Image(): void
    {
        $base64 = base64_encode($this->getCorruptedPngContent());

        $pdf = new PluginPdfSimplePDF();
        $pdf->newPage();
        $pdf->displayText('Description', '<p>before <img src="data:image/png;base64,' . $base64 . '" width="10"> after</p>');

        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringNotContainsString('/Subtype /Image', $output);
    }

    /**
     * Create a document whose file is stored in GLPI_DOC_DIR
     */
    private function createDocumentFile(string $dir, string $extension, string $content): int
    {
        global $DB;

        $this->login();

        $filepath = $dir . '/' . uniqid('', true) . '.' . $extension;
        $file = GLPI_DOC_DIR . '/' . $filepath;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0o777, true);
        }
        file_put_contents($file, $content);
        $this->files_to_remove[] = $file;

        $DB->insert(Document::getTable(), [
            'name'        => 'test document',
            'filename'    => 'test.' . $extension,
            'filepath'    => $filepath,
            'mime'        => 'image/png',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        return $DB->insertId();
    }

    /**
     * Get the HTML of an image inserted in a rich text
     */
    private function getDocumentImageHtml(int $docid): string
    {
        $url = '/front/document.send.php?docid=' . $docid;

        return '<p>before</p><a href="' . $url . '"><img src="' . $url . '"></a><p>after</p>';
    }

    /**
     * Build a valid 2x2 PNG file
     */
    private function getValidPngContent(): string
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    /**
     * Build a PNG file with a valid RGBA header but undecodable image data
     */
    private function getCorruptedPngContent(): string
    {
        $chunk = (static fn(string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data)));

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', 2, 2, 8, 6, 0, 0, 0))
            . $chunk('IDAT', 'not zlib compressed data')
            . $chunk('IEND', '');
    }
}
