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
class PluginPdfItem_Problem extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Item_Problem());
    }

    public static function pdfForProblem(PluginPdfSimplePDF $pdf, Problem $problem)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $instID = $problem->fields['id'];

        if (!$problem->can($instID, READ)) {
            return false;
        }

        $result = $DB->request(
            ['FROM' => 'glpi_items_problems'] + ['SELECT'      => 'itemtype',
                'DISTINCT' => true,
                'WHERE'    => ['problems_id' => $instID],
                'ORDER'    => 'itemtype'],
        );

        $number = count($result);
        $totalnb = 0;

        $pdf->setColumnsSize(100);
        $title = '<b>' . _sn('Item', 'Items', 2) . '</b>';
        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $title = sprintf(__s('%1$s: %2$s'), $title, $number);
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(20, 20, 26, 17, 17);
            $pdf->displayTitle(
                '<i>' . __s('Type'),
                __s('Name'),
                __s('Entity'),
                __s('Serial number'),
                __s('Inventory number') . '</i>',
            );

            foreach ($result as $row) {
                $itemtype = $row['itemtype'];
                if (!($item = $dbu->getItemForItemtype($itemtype))) {
                    continue;
                }

                if ($item->canView()) {
                    $itemtable = $dbu->getTableForItemType($itemtype);

                    $query_params = [
                        'SELECT' => [
                            "$itemtable.*",
                            'glpi_items_problems.id AS IDD',
                            'glpi_entities.id AS entity',
                        ],
                        'FROM' => 'glpi_items_problems',
                        'INNER JOIN' => [
                            $itemtable => [
                                'ON' => [
                                    $itemtable => 'id',
                                    'glpi_items_problems' => 'items_id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            'glpi_items_problems.itemtype' => $itemtype,
                            'glpi_items_problems.problems_id' => $instID,
                        ],
                        'ORDER' => ['glpi_entities.completename', "$itemtable.name"],
                    ];

                    if ($itemtype != 'Entity') {
                        $query_params['LEFT JOIN']['glpi_entities'] = [
                            'ON' => [
                                $itemtable => 'entities_id',
                                'glpi_entities' => 'id',
                            ],
                        ];
                    }

                    if ($item->maybeTemplate()) {
                        $query_params['WHERE']["$itemtable.is_template"] = 0;
                    }

                    // Ajout de la restriction d'entités
                    $entity_restrict = $dbu->getEntitiesRestrictRequest(
                        '',
                        $itemtable,
                        '',
                        '',
                        $item->maybeRecursive(),
                    );

                    if (!empty($entity_restrict)) {
                        $query_params['WHERE'][] = new QueryExpression($entity_restrict);
                    }

                    $result_linked = $DB->request($query_params);
                    $nb            = count($result_linked);

                    $prem = true;
                    foreach ($result_linked as $data) {
                        $name = $data['name'];
                        if (empty($data['name'])) {
                            $name = '(' . $data['id'] . ')';
                        }
                        if ($prem) {
                            $typename = $item->getTypeName($nb);
                            $pdf->displayLine(
                                Toolbox::stripTags(sprintf(__s('%1$s: %2$s'), $typename, $nb)),
                                Toolbox::stripTags($name),
                                Dropdown::getDropdownName('glpi_entities', $data['entity']),
                                Toolbox::stripTags($data['serial']),
                                Toolbox::stripTags($data['otherserial']),
                                $nb,
                            );
                        } else {
                            $pdf->displayLine(
                                '',
                                Toolbox::stripTags($name),
                                Dropdown::getDropdownName('glpi_entities', $data['entity']),
                                Toolbox::stripTags($data['serial']),
                                Toolbox::stripTags($data['otherserial']),
                                $nb,
                            );
                        }
                        $prem = false;
                    }
                    $totalnb += $nb;
                }
            }
        }
        $pdf->displayLine('<b><i>' . sprintf(__s('%1$s = %2$s') . '</b></i>', __s('Total'), $totalnb));
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item, $tree = false)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $restrict = '';
        $order    = '';
        switch ($item->getType()) {
            case 'User':
                $restrict = "(`glpi_problems_users`.`users_id` = '" . $item->getID() . "')";
                $order    = '`glpi_problems`.`date_mod` DESC';
                break;

            case 'Supplier':
                $restrict = "(`glpi_problems_suppliers`.`suppliers_id` = '" . $item->getID() . "')";
                $order    = '`glpi_problems`.`date_mod` DESC';
                break;

            case 'Group':
                if ($tree) {
                    $restrict = 'IN (' . implode(',', $dbu->getSonsOf('glpi_groups', $item->getID())) . ')';
                } else {
                    $restrict = "='" . $item->getID() . "'";
                }
                $restrict = "(`glpi_groups_problems`.`groups_id` $restrict)";
                $order    = '`glpi_problems`.`date_mod` DESC';
                break;

            default:
                $restrict = "(`items_id` = '" . $item->getID() . "'
                            AND `itemtype` = '" . $item->getType() . "')";
                $order = '`glpi_problems`.`date_mod` DESC';
                break;
        }

        $select_fields = [
            'glpi_problems.*',
            'glpi_itilcategories.completename AS catname',
        ];

        if (count($_SESSION['glpiactiveentities']) > 1) {
            $select_fields[] = 'glpi_entities.completename AS entityname';
            $select_fields[] = 'glpi_problems.entities_id AS entityID';
        }

        $left_joins = [
            'glpi_items_problems' => [
                'ON' => [
                    'glpi_problems' => 'id',
                    'glpi_items_problems' => 'problems_id',
                ],
            ],
            'glpi_groups_problems' => [
                'ON' => [
                    'glpi_problems' => 'id',
                    'glpi_groups_problems' => 'problems_id',
                ],
            ],
            'glpi_problems_users' => [
                'ON' => [
                    'glpi_problems' => 'id',
                    'glpi_problems_users' => 'problems_id',
                ],
            ],
            'glpi_problems_suppliers' => [
                'ON' => [
                    'glpi_problems' => 'id',
                    'glpi_problems_suppliers' => 'problems_id',
                ],
            ],
            'glpi_itilcategories' => [
                'ON' => [
                    'glpi_problems' => 'itilcategories_id',
                    'glpi_itilcategories' => 'id',
                ],
            ],
        ];

        if (count($_SESSION['glpiactiveentities']) > 1) {
            $left_joins['glpi_entities'] = [
                'ON' => [
                    'glpi_entities' => 'id',
                    'glpi_problems' => 'entities_id',
                ],
            ];
        }

        $where_conditions = [];

        if (str_contains($restrict, 'IN (') || str_contains($restrict, 'AND') || str_contains($restrict, 'OR')) {
            $where_conditions[] = new QueryExpression($restrict);
        } else {
            $where_conditions[] = new QueryExpression($restrict);
        }

        $entity_restrict = $dbu->getEntitiesRestrictRequest('', 'glpi_problems');
        if (!empty($entity_restrict)) {
            $where_conditions[] = new QueryExpression($entity_restrict);
        }

        $query_params = [
            'SELECT' => $select_fields,
            'DISTINCT' => true,
            'FROM' => 'glpi_problems',
            'LEFT JOIN' => $left_joins,
            'WHERE' => $where_conditions,
            'ORDER' => $order,
            'LIMIT' => intval($_SESSION['glpilist_limit']),
        ];

        $result = $DB->request($query_params);
        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . Problem::getTypeName($number) . '</b>';
        if ($number === 0) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $pdf->displayTitle('<b>' . sprintf(
                _sn('Last %d problem', 'Last %d problems', $number) . '</b>',
                $number,
            ));
        }

        $job = new Problem();
        foreach ($result as $data) {
            if (!$job->getFromDB($data['id'])) {
                continue;
            }
            $pdf->setColumnsAlign('center');
            $col = '<b><i>ID ' . $job->fields['id'] . '</i></b>, ' .
                          sprintf(__s('%1$s: %2$s'), __s('Status'), Problem::getStatus($job->fields['status']));

            if (count($_SESSION['glpiactiveentities']) > 1) {
                if ($job->fields['entities_id'] == 0) {
                    $col = sprintf(__s('%1$s (%2$s)'), $col, __s('Root entity'));
                } else {
                    $col = sprintf(
                        __s('%1$s (%2$s)'),
                        $col,
                        Dropdown::getDropdownName('glpi_entities', $job->fields['entities_id']),
                    );
                }
            }
            $pdf->displayLine($col);

            $pdf->setColumnsAlign('left');

            $col = '<b><i>' . sprintf(
                __s('Opened on %s') . '</i></b>',
                Html::convDateTime($job->fields['date']),
            );
            if ($job->fields['begin_waiting_date']) {
                $col = sprintf(
                    __s('%1$s, %2$s'),
                    $col,
                    '<b><i>' . sprintf(
                        __s('Put on hold on %s') . '</i></b>',
                        Html::convDateTime($job->fields['begin_waiting_date']),
                    ),
                );
            }
            if (in_array($job->fields['status'], $job->getSolvedStatusArray())
                || in_array($job->fields['status'], $job->getClosedStatusArray())) {
                $col = sprintf(
                    __s('%1$s, %2$s'),
                    $col,
                    '<b><i>' . sprintf(
                        __s('Solved on %s') . '</i></b>',
                        Html::convDateTime($job->fields['solvedate']),
                    ),
                );
            }
            if (in_array($job->fields['status'], $job->getClosedStatusArray())) {
                $col = sprintf(
                    __s('%1$s, %2$s'),
                    $col,
                    '<b><i>' . sprintf(
                        __s('Closed on %s') . '</i></b>',
                        Html::convDateTime($job->fields['closedate']),
                    ),
                );
            }
            if ($job->fields['time_to_resolve']) {
                $col = sprintf(
                    __s('%1$s, %2$s'),
                    $col,
                    '<b><i>' . sprintf(
                        __s('%1$s: %2$s') . '</i></b>',
                        __s('Time to resolve'),
                        Html::convDateTime($job->fields['time_to_resolve']),
                    ),
                );
            }
            $pdf->displayLine($col);

            $col = '<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Priority') . '</i></b>',
                Problem::getPriorityName($job->fields['priority']),
            );
            if ($job->fields['itilcategories_id']) {
                $cat = '<b><i>' . sprintf(
                    __s('%1$s: %2$s'),
                    __s('Category') . '</i></b>',
                    Dropdown::getDropdownName(
                        'glpi_itilcategories',
                        $job->fields['itilcategories_id'],
                    ),
                );
                $col = sprintf(__s('%1$s - %2$s'), $col, $cat);
            }
            $pdf->displayLine($col);

            $lastupdate = Html::convDateTime($job->fields['date_mod']);
            if ($job->fields['users_id_lastupdater'] > 0) {
                $lastupdate = sprintf(
                    __s('%1$s by %2$s'),
                    $lastupdate,
                    $dbu->getUserName($job->fields['users_id_lastupdater']),
                );
            }

            $pdf->displayLine('<b><i>' . sprintf(
                __s('%1$s: %2$s'),
                __s('Last update') . '</i></b>',
                $lastupdate,
            ));

            $col   = '';
            $users = $job->getUsers(CommonITILActor::REQUESTER);
            if (count($users)) {
                foreach ($users as $d) {
                    if (empty($col)) {
                        $col = $dbu->getUserName($d['users_id']);
                    } else {
                        $col = sprintf(__s('%1$s, %2$s'), $col, $dbu->getUserName($d['users_id']));
                    }
                }
            }
            $grps = $job->getGroups(CommonITILActor::REQUESTER);
            if (count($grps)) {
                if (empty($col)) {
                    $col = sprintf(__s('%1$s %2$s'), $col, _sn('Group', 'Groups', 2) . ' </i></b>');
                } else {
                    $col = sprintf(__s('%1$s - %2$s'), $col, _sn('Group', 'Groups', 2) . ' </i></b>');
                }
                $first = true;
                foreach ($grps as $d) {
                    if ($first) {
                        $col = sprintf(
                            __s('%1$s  %2$s'),
                            $col,
                            Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                        );
                    } else {
                        $col = sprintf(
                            __s('%1$s, %2$s'),
                            $col,
                            Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                        );
                    }
                    $first = false;
                }
            }
            if ($col) {
                $texte = '<b><i>' . sprintf(__s('%1$s: %2$s'), __s('Requester') . '</i></b>', '');
                $pdf->displayText($texte, $col, 1);
            }

            $col   = '';
            $users = $job->getUsers(CommonITILActor::ASSIGN);
            if (count($users)) {
                foreach ($users as $d) {
                    if (empty($col)) {
                        $col = $dbu->getUserName($d['users_id']);
                    } else {
                        $col = sprintf(__s('%1$s, %2$s'), $col, $dbu->getUserName($d['users_id']));
                    }
                }
            }
            $grps = $job->getGroups(CommonITILActor::ASSIGN);
            if (count($grps)) {
                if (empty($col)) {
                    $col = sprintf(__s('%1$s %2$s'), $col, _sn('Group', 'Groups', 2) . ' </i></b>');
                } else {
                    $col = sprintf(__s('%1$s - %2$s'), $col, _sn('Group', 'Groups', 2) . ' </i></b>');
                }
                $first = true;
                foreach ($grps as $d) {
                    if ($first) {
                        $col = sprintf(
                            __s('%1$s  %2$s'),
                            $col,
                            Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                        );
                    } else {
                        $col = sprintf(
                            __s('%1$s, %2$s'),
                            $col,
                            Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                        );
                    }
                    $first = false;
                }
            }
            if ($col) {
                $texte = '<b><i>' . sprintf(__s('%1$s: %2$s') . '</i></b>', __s('Assigned to'), '');
                $pdf->displayText($texte, $col, 1);
            }

            $texte = '<b><i>' . sprintf(__s('%1$s: %2$s') . '</i></b>', __s('Associated items'), '');

            $texte = '<b><i>' . sprintf(__s('%1$s: %2$s') . '</i></b>', __s('Title'), '');
            $pdf->displayText($texte, $job->fields['name'], 1);
        }
        $pdf->displaySpace();
    }
}
