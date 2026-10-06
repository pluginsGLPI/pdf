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

use Glpi\Tests\GLPITestCase;

class SimplePDFTest extends GLPITestCase
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
}
