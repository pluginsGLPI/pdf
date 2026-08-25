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

use Glpi\Tests\GLPITestCase;
use PluginPdfSimplePDF;
use ReflectionMethod;

class SimplePdfTest extends GLPITestCase
{
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
        $method->setAccessible(true);
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
}
