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
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Pdf\Tests\Units;

use Computer;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Pdf\Tests\RecordingSimplePDF;
use Item_SoftwareLicense;
use PluginPdfItem_SoftwareVersion;
use Software;
use SoftwareLicense;
use SoftwareVersion;

class ItemSoftwareVersionTest extends DbTestCase
{
    public function testPdfForItemDisplaysBoughtVersionOfNotInstalledLicense(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(true);

        $computer = $this->createItem(Computer::class, ['name' => 'PDF computer', 'entities_id' => $entity_id]);
        $software = $this->createItem(Software::class, ['name' => 'PDF software', 'entities_id' => $entity_id]);
        $version_bought = $this->createItem(SoftwareVersion::class, [
            'name'         => 'PDF version bought',
            'softwares_id' => $software->getID(),
            'entities_id'  => $entity_id,
        ]);
        $this->createItem(SoftwareVersion::class, [
            'name'         => 'PDF version other',
            'softwares_id' => $software->getID(),
            'entities_id'  => $entity_id,
        ]);
        $license = $this->createItem(SoftwareLicense::class, [
            'name'                    => 'PDF license',
            'softwares_id'            => $software->getID(),
            'softwareversions_id_buy' => $version_bought->getID(),
            'softwareversions_id_use' => 0,
            'entities_id'             => $entity_id,
        ]);
        $this->createItem(Item_SoftwareLicense::class, [
            'softwarelicenses_id' => $license->getID(),
            'itemtype'            => Computer::class,
            'items_id'            => $computer->getID(),
        ]);

        $pdf = new RecordingSimplePDF();
        PluginPdfItem_SoftwareVersion::pdfForItem($pdf, $computer);

        $this->assertContains('PDF version bought', $pdf->cells);
        $this->assertNotContains('PDF version other', $pdf->cells);
    }
}
