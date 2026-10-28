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

use Glpi\DBAL\QueryExpression;

class PluginPdfUser extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new User());
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, User $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $item->getField('id');

        $pdf->setColumnsSize(50, 50);
        $pdf->displayTitle(
            '<b>' . sprintf(__s('%1$s %2$s'), __s('ID'), $item->fields['id']) . '</b>',
            sprintf(
                __s('%1$s: %2$s'),
                __s('Last update'),
                Html::convDateTime($item->fields['date_mod']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Login') . '</i></b>', $item->fields['name']),
            '<b><i>' . sprintf(
                __s('Last login on %s') . '</i></b>',
                Html::convDateTime($item->fields['last_login']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Surname'), $item->fields['realname'] . '</i></b>'),
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                __s('First name') . '</i></b>',
                $item->fields['firstname'],
            ),
        );

        $end = '';
        if ($item->fields['end_date']) {
            $end = '<b><i> - ' . sprintf(
                __s('%1$s - %2$s'),
                __s('Valid until') . '</i></b>',
                Html::convDateTime($item->fields['end_date']),
            );
        }
        $pdf->displayLine(
            '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Active') . '</i></b>', $item->fields['is_active']),
            '<b><i>' . sprintf(
                __s('%1$s : %2$s'),
                __s('Valid since') . '</i></b>',
                Html::convDateTime($item->fields['begin_date']) . $end,
            ),
        );

        $emails = [];
        foreach ($DB->request(['FROM' => 'glpi_useremails', 'WHERE' => ['users_id' => $item->getField('id')]]) as $key => $email) {
            $emails[] = $email['is_default'] == 1 ? $email['email'] . ' (' . __s('Default email') . ')' : $email['email'];
        }
        $pdf->setColumnsSize(100);
        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                _sn('Email', 'Emails', Session::getPluralNumber()) . '</i></b>',
                implode(', ', $emails),
            ),
        );

        $pdf->setColumnsSize(50, 50);
        $pdf->displayLine(
            '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Phone') . '</i></b>', $item->fields['phone']),
            '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Phone 2') . '</i></b>', $item->fields['phone2']),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Mobile phone') . '</i></b>',
                $item->fields['mobile'],
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Category') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_usercategories',
                    $item->fields['usercategories_id'],
                ),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Administrative number') . '</i></b>',
                $item->fields['registration_number'],
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                _x('person', 'Title') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_usertitles',
                    $item->fields['usertitles_id'],
                ),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Location') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_locations',
                    $item->fields['locations_id'],
                ),
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Language') . '</i></b>',
                Dropdown::getLanguageName($item->fields['language']),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Default profile') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_profiles',
                    $item->fields['profiles_id'],
                ),
            ),
            '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Default entity') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_entities',
                    $item->fields['entities_id'],
                ),
            ),
        );

        PluginPdfCommon::mainLine($pdf, $item, 'comment');

        $pdf->displaySpace();
    }

    public static function pdfItems(PluginPdfSimplePDF $pdf, User $user, $tech)
    {
        /** @var array $CFG_GLPI */
        /** @var DBmysql $DB */
        global $CFG_GLPI, $DB;

        $dbu = new DbUtils();

        $ID = $user->getField('id');

        if ($tech) {
            $type_user   = $CFG_GLPI['linkuser_tech_types'];
            $type_group  = $CFG_GLPI['linkgroup_tech_types'];
            $field_user  = 'users_id_tech';
            $field_group = 'groups_id_tech';
            $title       = __s('Managed items');
            $conso       = false;
        } else {
            $type_user   = $CFG_GLPI['linkuser_types'];
            $type_group  = $CFG_GLPI['linkgroup_types'];
            $field_user  = 'users_id';
            $field_group = 'groups_id';
            $title       = __s('Used items');
            $conso       = true;
        }

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . $title . '</b>');

        $pdf->setColumnsSize(15, 15, 15, 15, 15, 15, 10);
        $pdf->displayTitle(
            __s('Type'),
            __s('Entity'),
            __s('Name'),
            __s('Serial number'),
            __s('Inventory number'),
            __s('Status'),
            '',
        );

        $empty = true;
        foreach ($type_user as $itemtype) {
            if (!($item = $dbu->getItemForItemtype($itemtype))) {
                continue;
            }
            if ($item->canView()) {
                $itemtable = $dbu->getTableForItemType($itemtype);

                $query = ['FROM' => $itemtable,
                    'WHERE'      => [$field_user => $ID]];

                if ($item->maybeTemplate()) {
                    $query['WHERE']['is_template'] = 0;
                }
                if ($item->maybeDeleted()) {
                    $query['WHERE']['is_deleted'] = 0;
                }

                $result = $DB->request($query);

                $type_name = $item->getTypeName();

                if (count($result) > 0) {
                    foreach ($result as $data) {
                        $name = $data['name'];
                        if (empty($name)) {
                            $name = sprintf(__s('%1$s (%2$s)'), $name, $data['id']);
                        }
                        $linktype = '';
                        if ($data[$field_user] == $ID) {
                            $linktype = User::getTypeName(1);
                        }
                        $pdf->displayLine(
                            $item->getTypeName(1),
                            Dropdown::getDropdownName('glpi_entities', $data['entities_id']),
                            $name,
                            $data['serial'] ?? '',
                            $data['otherserial'] ?? '',
                            isset($data['states_id'])
                             ? Dropdown::getDropdownName('glpi_states', $data['states_id'])
                             : '',
                            $linktype,
                        );
                    }
                    $empty = false;
                }
            }
        }
        if (!$empty) {
            $pdf->setColumnsSize(15, 15, 15, 15, 15, 15, 10);
            $pdf->displayTitle(
                __s('Type'),
                __s('Entity'),
                __s('Name'),
                __s('Serial number'),
                __s('Inventory number'),
                __s('Status'),
                '',
            );
        }

        $groups_ids = [];
        foreach ($DB->request(['SELECT' => 'groups_id', 'FROM' => 'glpi_groups_users', 'WHERE' => ['users_id' => $ID]]) as $data) {
            $groups_ids[] = $data['groups_id'];
        }

        if ($groups_ids !== []) {
            $empty = false;

            foreach ($type_group as $itemtype) {
                if (!($item = $dbu->getItemForItemtype($itemtype))) {
                    continue;
                }
                if ($item->canView()) {
                    $itemtable = $dbu->getTableForItemType($itemtype);

                    $query = [
                        'SELECT' => [
                            "$itemtable.*",
                            new QueryExpression(
                                'GROUP_CONCAT(DISTINCT ' . $DB::quoteName(Group_Item::getTable() . '.groups_id') . ') AS ' . $DB::quoteName('groups_ids'),
                            ),
                        ],
                        'FROM'       => $itemtable,
                        'INNER JOIN' => [
                            Group_Item::getTable() => [
                                'FKEY' => [
                                    $itemtable             => 'id',
                                    Group_Item::getTable() => 'items_id',
                                    ['AND' => [Group_Item::getTable() . '.itemtype' => $itemtype]],
                                ],
                            ],
                        ],
                        'WHERE' => [
                            Group_Item::getTable() . '.groups_id' => $groups_ids,
                            Group_Item::getTable() . '.type'      => $tech ? Group_Item::GROUP_TYPE_TECH : Group_Item::GROUP_TYPE_NORMAL,
                        ],
                        'GROUPBY' => "$itemtable.id",
                    ];

                    if ($item->maybeTemplate()) {
                        $query['WHERE']["$itemtable.is_template"] = 0;
                    }
                    if ($item->maybeDeleted()) {
                        $query['WHERE']["$itemtable.is_deleted"] = 0;
                    }

                    foreach ($DB->request($query) as $data) {
                        $name = $data['name'];
                        if (empty($name)) {
                            $name = sprintf(__s('%1$s (%2$s)'), $name, $data['id']);
                        }
                        $item_groups_ids = explode(',', $data['groups_ids']);
                        $pdf->displayLine(
                            $item->getTypeName(1),
                            Dropdown::getDropdownName('glpi_entities', $data['entities_id']),
                            $name,
                            $data['serial'] ?? '',
                            $data['otherserial'] ?? '',
                            isset($data['states_id'])
                             ? Dropdown::getDropdownName('glpi_states', $data['states_id'])
                             : '',
                            sprintf(
                                __s('%1$s = %2$s'),
                                _sn('Group', 'Groups', count($item_groups_ids)),
                                PluginPdfCommon::getGroupNames($item_groups_ids),
                            ),
                        );
                    }
                }
            }
        }
        if ($empty) {
            $pdf->setColumnsSize(100);
            $pdf->displayLine(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        }
        $pdf->displaySpace();

        if ($conso) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>' . __s('Used consumables') . '</b>');

            $pdf->setColumnsSize(70, 30);
            $pdf->displayTitle(__s('Name'), __s('Use date'));

            $iterator = $DB->request(['FROM' => 'glpi_consumables',
                'LEFT JOIN'                  => ['glpi_consumableitems'
                                => ['FKEY' => ['glpi_consumables' => 'consumableitems_id',
                                    'glpi_consumableitems'        => 'id']]],
                'WHERE' => ['NOT' => ['date_out' => 'NULL'],
                    'itemtype'    => 'User',
                    'items_id'    => $ID],
                'ORDER' => 'date_out DESC']);

            foreach ($iterator as $dataconso) {
                $pdf->displayLine($dataconso['name'], Html::convDate($dataconso['date_out']));
            }
        }
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);
        unset($onglets['Profile_User$1']);
        unset($onglets['Group_User$1']);
        unset($onglets['Config$1']);
        unset($onglets['Synchronisation$1']);
        unset($onglets['Certificate_Item$1']);
        unset($onglets['Auth$1']);

        return $onglets;
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof User) {
            switch ($tab) {
                case 'User$1':
                    self::pdfItems($pdf, $item, false);
                    break;

                case 'User$2':
                    self::pdfItems($pdf, $item, true);
                    break;

                case 'Reservation$1':
                    PluginPdfReservation::pdfForUser($pdf, $item);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }
}
