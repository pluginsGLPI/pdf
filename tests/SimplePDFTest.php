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
 * @author    Teclib
 * @copyright Copyright (c) 2009-2026 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

use Glpi\Tests\DbTestCase;

class SimplePDFTest extends DbTestCase
{
    private function getStringWidth(PluginPdfSimplePDF $pdf, string $string): float
    {
        $property = new ReflectionProperty(PluginPdfSimplePDF::class, 'pdf');

        return $property->getValue($pdf)->GetStringWidth($string);
    }

    public function testWrapCellContentLeavesHtmlUntouched(): void
    {
        $pdf = new PluginPdfSimplePDF();
        $html = '<b>' . str_repeat('a', 200) . '</b>';

        $this->assertSame($html, $this->callPrivateMethod($pdf, 'wrapCellContent', $html, 10));
    }

    public function testWrapCellContentLeavesContentUntouchedWhenWidthIsNotPositive(): void
    {
        $pdf = new PluginPdfSimplePDF();
        $msg = str_repeat('a', 200);

        $this->assertSame($msg, $this->callPrivateMethod($pdf, 'wrapCellContent', $msg, 0));
    }

    public function testBreakWordToFitLeavesWordUntouchedWhenItAlreadyFits(): void
    {
        $pdf = new PluginPdfSimplePDF();

        $this->assertSame('short', $this->callPrivateMethod($pdf, 'breakWordToFit', 'short', 100));
    }

    public function testBreakWordToFitSplitsOnDelimiters(): void
    {
        $pdf = new PluginPdfSimplePDF();
        $word = str_repeat('a', 20) . '/' . str_repeat('b', 20) . '-' . str_repeat('c', 20);
        $width = $this->getStringWidth($pdf, str_repeat('a', 30));

        $result = $this->callPrivateMethod($pdf, 'breakWordToFit', $word, $width);
        $chunks = explode(' ', $result);

        $this->assertSame($word, str_replace(' ', '', $result));
        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual($width, $this->getStringWidth($pdf, $chunk));
        }
    }

    public function testBreakWordToFitFallsBackToCharacterSplitWithoutDelimiters(): void
    {
        $pdf = new PluginPdfSimplePDF();
        $word = str_repeat('a', 200);
        $width = $this->getStringWidth($pdf, str_repeat('a', 10));

        $result = $this->callPrivateMethod($pdf, 'breakWordToFit', $word, $width);
        $chunks = explode(' ', $result);

        $this->assertSame($word, str_replace(' ', '', $result));
        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertNotSame('', $chunk);
            $this->assertLessThanOrEqual($width, $this->getStringWidth($pdf, $chunk));
        }
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
        $cleaned = $this->callPrivateMethod($pdf, 'cleanTableHtml', $html);

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
     * Test a large document image inside a table
     * Its base64 data made the table regex hit `pcre.backtrack_limit`, and the whole content vanished from the PDF
     */
    public function testDisplayTextEmbedsLargeDocumentImageInTable(): void
    {
        $this->login();

        // ~1 MB PNG: random pixels are not compressible
        $image = imagecreatetruecolor(600, 600);
        for ($x = 0; $x < 600; $x++) {
            for ($y = 0; $y < 600; $y++) {
                imagesetpixel($image, $x, $y, random_int(0, 0xFFFFFF));
            }
        }
        $filename = uniqid('glpitest_', true) . '.png';
        $this->assertTrue(imagepng($image, GLPI_TMP_DIR . '/' . $filename, 0));
        $this->assertGreaterThan(1_000_000, filesize(GLPI_TMP_DIR . '/' . $filename));

        $document = $this->createItem(
            Document::class,
            [
                'filename'    => $filename,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                '_filename'   => [$filename],
            ],
            ['filename', '_filename'],
        );

        $pdf = new PluginPdfSimplePDF();
        $pdf->newPage();
        $pdf->displayText(
            'Description',
            '<table><tr><td><a href="/front/document.send.php?docid=' . $document->getID() . '">'
            . '<img src="/front/document.send.php?docid=' . $document->getID() . '" width="100"></a></td></tr></table>',
        );

        $this->assertStringContainsString('/Subtype /Image', $pdf->output());
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
