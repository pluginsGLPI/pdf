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

class PluginPdfGroup extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Group());
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, Group $item)
    {
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

        $pdf->setColumnsSize(100);
        $pdf->displayLine('<b><i>' . sprintf(
            __s('%1$s: %2$s'),
            __s('Complete name') . '</i></b>',
            $item->fields['completename'],
        ));

        $pdf->setColumnsAlign('center');
        $pdf->displayLine('<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Visible in a ticket'), '' . '</i></b>'));
        $pdf->setColumnsSize(20, 20, 20, 20, 20);
        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                __s('Requester') . '</i></b>',
                Dropdown::getYesNo($item->fields['is_requester']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                _n('Observer', 'Observers', 1) . '</i></b>',
                Dropdown::getYesNo($item->fields['is_watcher']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                __s('Assigned to') . '</i></b>',
                Dropdown::getYesNo($item->fields['is_assign']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                __s('Task') . '</i></b>',
                Dropdown::getYesNo($item->fields['is_task']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                __s('Can be notified') . '</i></b>',
                Dropdown::getYesNo($item->fields['is_notify']),
            ),
        );

        $pdf->setColumnsSize(100);
        $pdf->setColumnsAlign('center');
        $pdf->displayLine('<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Visible in a project'), ''));
        $pdf->setColumnsAlign('left');
        $pdf->displayLine('<b><i>' . sprintf(
            __s('%1$s - %2$s'),
            __s('Can be manager') . '</i></b>',
            Dropdown::getYesNo($item->fields['is_manager']),
        ));

        $pdf->setColumnsSize(100);
        $pdf->setColumnsAlign('center');
        $pdf->displayLine('<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Can contain'), ''));
        $pdf->setColumnsSize(50, 50);
        $pdf->displayLine(
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                _sn('Item', 'Items', 2) . '</i></b>',
                Dropdown::getYesNo($item->fields['is_itemgroup']),
            ),
            '<b><i>' . sprintf(
                __s('%1$s - %2$s'),
                _sn('User', 'Users', 2) . '</i></b>',
                Dropdown::getYesNo($item->fields['is_usergroup']),
            ),
        );

        PluginPdfCommon::mainLine($pdf, $item, 'comment');

        $pdf->displaySpace();
    }

    // From Group::showLDAPForm()
    public static function pdfLdapForm(PluginPdfSimplePDF $pdf, Group $item)
    {
        if (Session::haveRight('config', READ) && AuthLDAP::useAuthLdap()) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>' . __s('LDAP directory link') . '</b>');

            $pdf->displayText(
                '<b>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('User attribute containing its groups') . '</b>',
                    '',
                ),
                $item->getField('ldap_field'),
            );
            $pdf->displayText(
                '<b>' . sprintf(__s('%1$s: %2$s'), __s('Attribute value') . '</b>', ''),
                $item->getField('ldap_value'),
            );
            $pdf->displayText(
                '<b>' . sprintf(__s('%1$s: %2$s'), __s('Group DN') . '</b>', ''),
                $item->getField('ldap_group_dn'),
            );

            $pdf->displaySpace();
        }
    }

    // From Group::showItems()
    public static function pdfItems(PluginPdfSimplePDF $pdf, Group $group, $tech, $tree, $user)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $dbu = new DbUtils();

        if ($tech) {
            $field = 'groups_id_tech';
            $title = __s('Managed items');
        } else {
            $field = 'groups_id';
            $title = __s('Used items');
        }

        $datas = [];
        $max   = $group->getDataItems($tech, $tree, $user, 0, $datas);
        $nb    = count($datas);

        $title = $nb < $max ? sprintf(__s('%1$s (%2$s)'), $title, $nb . '/' . $max) : sprintf(__s('%1$s (%2$s)'), $title, $nb);
        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . $title . '</b>');

        if ($nb !== 0) {
            if ($tree || $user) {
                $pdf->setColumnsSize(16, 20, 34, 30);
                $pdf->displayTitle(
                    __s('Type'),
                    __s('Name'),
                    __s('Entity'),
                    Group::getTypeName(1) . ' / ' . User::getTypeName(1),
                );
            } else {
                $pdf->setColumnsSize(20, 25, 55);
                $pdf->displayTitle(__s('Type'), __s('Name'), __s('Entity'));
            }
        } else {
            $pdf->displayLine(__s('No item found'));
        }

        $tmpgrp = new Group();
        new User();

        foreach ($datas as $data) {
            if (!($item = $dbu->getItemForItemtype($data['itemtype']))) {
                continue;
            }
            $item->getFromDB($data['items_id']);

            $col4 = '';
            if ($tree || $user) {
                if ($grp = $item->getField($field)) {
                    if ($tmpgrp->getFromDB($grp)) {
                        $col4 = $tmpgrp->getNameID();
                    }
                } elseif ($usr = $item->getField(str_replace('groups', 'users', $field))) {
                    $col4 = Toolbox::stripTags($dbu->getUserName($usr));
                }
            }
            $pdf->displayLine(
                $item->getTypeName(1),
                $item->getName(),
                Dropdown::getDropdownName('glpi_entities', $item->getEntityID()),
                $col4,
            );
        }
        $pdf->displaySpace();
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);

        unset($onglets['NotificationTarget$1']);

        return $onglets;
    }

    public static function pdfChildren(PluginPdfSimplePDF $pdf, CommonTreeDropdown $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $item->getID();
        $item->getAdditionalFields();
        $entity_assign = $item->isEntityAssign();

        $fk   = $item->getForeignKeyField();
        $crit = [
            'FROM' => $item->getTable(),
            'WHERE' => [
                $fk => $item->getID(),
            ],
            'ORDER' => 'name',
        ];

        if ($item->haveChildren()) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle(sprintf(__s('Sons of %s'), '<b>' . $item->getNameID() . '</b>'));

            if ($entity_assign) {
                if ($fk == 'entities_id') {
                    $crit['WHERE']['id'] = $_SESSION['glpiactiveentities'] + $_SESSION['glpiparententities'];
                } else {
                    $crit['WHERE']['entities_id'] = $_SESSION['glpiactiveentities'];
                }

                $pdf->setColumnsSize(30, 30, 40);
                $pdf->displayTitle(__s('Name'), __s('Entity'), __s('Comments'));
            } else {
                $pdf->setColumnsSize(45, 55);
                $pdf->displayTitle(__s('Name'), __s('Comments'));
            }

            foreach ($DB->request($crit) as $data) {
                if ($entity_assign) {
                    $pdf->displayLine(
                        $data['name'],
                        Dropdown::getDropdownName('glpi_entities', $data['entities_id']),
                        $data['comment'],
                    );
                } else {
                    $pdf->displayLine($data['name'], $data['comment']);
                }
            }
        } else {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>' . sprintf(__s('No sons of %s', 'behaviors'), $item->getNameID() . '</b>'));
        }

        $pdf->displaySpace();
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        $tree = isset($_REQUEST['item']['_tree']);
        $user = isset($_REQUEST['item']['_user']);

        if ($item instanceof Group) {
            switch ($tab) {
                case 'Group$1':
                    self::pdfItems($pdf, $item, false, $tree, $user);
                    break;

                case 'Group$2':
                    self::pdfItems($pdf, $item, true, $tree, $user);
                    break;

                case 'Group$3':
                    self::pdfLdapForm($pdf, $item);
                    break;

                case 'Group$4':
                    self::pdfChildren($pdf, $item);
                    break;

                case 'Group_User$1':
                    PluginPdfGroup_User::pdfForGroup($pdf, $item, $tree);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }
}
