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

namespace GlpiPlugin\Pdf\Tests\Units;

use Computer;
use Glpi\Tests\DbTestCase;
use Group;
use Group_Item;
use ITILCategory;
use PluginPdfCommon;
use ReflectionMethod;

final class GetGroupNameTest extends DbTestCase
{
    private function getGroupName(\CommonDBTM $item, int $group_type = Group_Item::GROUP_TYPE_NORMAL): string
    {
        $method = new ReflectionMethod(PluginPdfCommon::class, 'getGroupName');
        $method->setAccessible(true);

        return $method->invoke(null, $item, $group_type);
    }

    /**
     * One-to-one relation: the group id is stored directly in the `groups_id` column
     * (e.g. glpi_itilcategories), as a scalar value.
     */
    public function testScalarGroupOnOneToOneRelation(): void
    {
        $group = $this->createItem(Group::class, [
            'name'        => 'One-to-one group',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $category = $this->createItem(ITILCategory::class, [
            'name'        => 'Category with a group',
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id'   => $group->getID(),
        ]);
        $category->getFromDB($category->getID());

        $this->assertSame($group->fields['name'], $this->getGroupName($category));
    }

    public function testNoGroupOnOneToOneRelation(): void
    {
        $category = $this->createItem(ITILCategory::class, [
            'name'        => 'Category without a group',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $category->getFromDB($category->getID());

        $this->assertSame('', $this->getGroupName($category));
    }

    /**
     * Many-to-many relation: groups are stored in the `glpi_groups_items` pivot table
     * (AssignableItem trait, e.g. glpi_computers), as an array of ids.
     */
    public function testArrayGroupsOnManyToManyRelation(): void
    {
        $group1 = $this->createItem(Group::class, [
            'name'        => 'Computer group 1',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $group2 = $this->createItem(Group::class, [
            'name'        => 'Computer group 2',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $tech_group = $this->createItem(Group::class, [
            'name'        => 'Computer tech group',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $computer = $this->createItem(Computer::class, [
            'name'            => 'Computer with groups',
            'entities_id'     => $this->getTestRootEntity(true),
            'groups_id'       => [$group1->getID(), $group2->getID()],
            'groups_id_tech'  => [$tech_group->getID()],
        ]);
        $computer->getFromDB($computer->getID());

        $this->assertSame(
            implode(', ', [$group1->fields['name'], $group2->fields['name']]),
            $this->getGroupName($computer)
        );
        $this->assertSame(
            $tech_group->fields['name'],
            $this->getGroupName($computer, Group_Item::GROUP_TYPE_TECH)
        );
    }

    public function testNoGroupsOnManyToManyRelation(): void
    {
        $computer = $this->createItem(Computer::class, [
            'name'        => 'Computer without groups',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $computer->getFromDB($computer->getID());

        $this->assertSame('', $this->getGroupName($computer));
        $this->assertSame('', $this->getGroupName($computer, Group_Item::GROUP_TYPE_TECH));
    }
}
