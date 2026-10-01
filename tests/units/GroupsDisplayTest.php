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

use Appliance;
use CartridgeItem;
use Computer;
use ConsumableItem;
use Domain;
use Domain_Item;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Pdf\Tests\RecordingSimplePDF;
use Group;
use Monitor;
use NetworkEquipment;
use Peripheral;
use Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PluginPdfAppliance;
use PluginPdfCartridgeItem;
use PluginPdfComputer;
use PluginPdfConsumableItem;
use PluginPdfDomain_Item;
use PluginPdfMonitor;
use PluginPdfNetworkEquipment;
use PluginPdfPeripheral;
use PluginPdfPhone;
use PluginPdfPrinter;
use PluginPdfSoftware;
use Printer;
use Software;

class GroupsDisplayTest extends DbTestCase
{
    private const GROUP_CELL      = '<b><i>Group</i></b>: %s';
    private const TECH_GROUP_CELL = '<b><i>Group in charge of the hardware</i></b>: %s';

    public static function pdfMainProvider(): iterable
    {
        $assets = [
            'Computer'         => [Computer::class, PluginPdfComputer::class],
            'Monitor'          => [Monitor::class, PluginPdfMonitor::class],
            'NetworkEquipment' => [NetworkEquipment::class, PluginPdfNetworkEquipment::class],
            'Peripheral'       => [Peripheral::class, PluginPdfPeripheral::class],
            'Phone'            => [Phone::class, PluginPdfPhone::class],
            'Printer'          => [Printer::class, PluginPdfPrinter::class],
            'Software'         => [Software::class, PluginPdfSoftware::class],
            'Appliance'        => [Appliance::class, PluginPdfAppliance::class],
        ];
        foreach ($assets as $label => [$itemtype, $pdf_class]) {
            yield "$label groups" => [
                'itemtype'  => $itemtype,
                'pdf_class' => $pdf_class,
                'field'     => 'groups_id',
                'template'  => self::GROUP_CELL,
            ];
            yield "$label tech groups" => [
                'itemtype'  => $itemtype,
                'pdf_class' => $pdf_class,
                'field'     => 'groups_id_tech',
                'template'  => self::TECH_GROUP_CELL,
            ];
        }
        yield 'CartridgeItem tech groups' => [
            'itemtype'  => CartridgeItem::class,
            'pdf_class' => PluginPdfCartridgeItem::class,
            'field'     => 'groups_id_tech',
            'template'  => self::TECH_GROUP_CELL,
        ];
        yield 'ConsumableItem tech groups' => [
            'itemtype'  => ConsumableItem::class,
            'pdf_class' => PluginPdfConsumableItem::class,
            'field'     => 'groups_id_tech',
            'template'  => self::TECH_GROUP_CELL,
        ];
    }

    #[DataProvider('pdfMainProvider')]
    public function testPdfMainDisplaysAllGroups(string $itemtype, string $pdf_class, string $field, string $template): void
    {
        $this->login();
        $group_b = $this->createItem(Group::class, ['name' => 'PDF group B', 'entities_id' => 0]);
        $group_a = $this->createItem(Group::class, ['name' => 'PDF group A', 'entities_id' => 0]);

        $item = $this->createItem($itemtype, [
            'name'        => 'PDF item',
            'entities_id' => 0,
            $field       => [$group_a->getID(), $group_b->getID()],
        ]);

        $pdf = new RecordingSimplePDF();
        $pdf_class::pdfMain($pdf, $item);

        $this->assertContains(sprintf($template, 'PDF group A, PDF group B'), $pdf->cells);
    }

    #[DataProvider('pdfMainProvider')]
    public function testPdfMainDisplaysNoGroup(string $itemtype, string $pdf_class, string $field, string $template): void
    {
        $this->login();
        $item = $this->createItem($itemtype, [
            'name'        => 'PDF item',
            'entities_id' => 0,
        ]);

        $pdf = new RecordingSimplePDF();
        $pdf_class::pdfMain($pdf, $item);

        $this->assertContains(sprintf($template, ''), $pdf->cells);
    }

    public function testDomainItemDisplaysAllTechGroups(): void
    {
        $this->login();
        $group_b = $this->createItem(Group::class, ['name' => 'PDF group B', 'entities_id' => 0]);
        $group_a = $this->createItem(Group::class, ['name' => 'PDF group A', 'entities_id' => 0]);

        $computer = $this->createItem(Computer::class, ['name' => 'PDF computer', 'entities_id' => 0]);
        $domain   = $this->createItem(Domain::class, [
            'name'           => 'pdf.example.com',
            'entities_id'    => 0,
            'groups_id_tech' => [$group_a->getID(), $group_b->getID()],
        ]);
        $this->createItem(Domain_Item::class, [
            'domains_id' => $domain->getID(),
            'itemtype'   => Computer::class,
            'items_id'   => $computer->getID(),
        ]);

        $pdf = new RecordingSimplePDF();
        PluginPdfDomain_Item::pdfForItem($pdf, $computer);

        $this->assertContains('PDF group A, PDF group B', $pdf->cells);
    }
}
