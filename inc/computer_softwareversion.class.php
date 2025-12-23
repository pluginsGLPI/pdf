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
class PluginPdfComputer_SoftwareVersion extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(?CommonGLPI $obj = null)
    {
        $this->obj = ($obj ?: new Item_SoftwareVersion());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $ID   = $item->getField('id');
        $type = $item->getType();
        $crit = ($type == 'Software' ? 'softwares_id' : 'id');


        $query_number = ['FROM' => 'glpi_computers_softwareversions', 'COUNT' => 'cpt',
            'INNER JOIN'        => ['glpi_computers'
                             => ['FKEY' => ['glpi_computers_softwareversions' => 'computers_id',
                                 'glpi_computers'                             => 'id']]],
            'WHERE' => ['glpi_computers.is_deleted'          => 0,
                'glpi_computers.is_template'                 => 0,
                'glpi_computers_softwareversions.is_deleted' => 0]
                             + $dbu->getEntitiesRestrictCriteria('glpi_computers')];

        if ($type == 'Software') {
            $crit = 'softwares_id';
            // Software ID
            $query_number['INNER JOIN']['glpi_softwareversions']
                              = ['FKEY' => ['glpi_computers_softwareversions' => 'softwareversions_id',
                                  'glpi_softwareversions'                     => 'id']];
            $query_number['WHERE']['glpi_softwareversions.softwares_id'] = $ID;
        } else {
            $crit = 'id';
            //SoftwareVersion ID
            $query_number['WHERE']['glpi_computers_softwareversions.softwareversions_id'] = $ID;
        }

        $total = 0;
        $result = $DB->request($query_number);
        foreach ($result as $row) {
            $total = $row['cpt'];
        }

        $query_params = [
            'SELECT' => [
                'glpi_computers_softwareversions.*',
                'glpi_computers.name AS compname',
                'glpi_computers.id AS cID',
                'glpi_computers.serial',
                'glpi_computers.otherserial',
                'glpi_users.name AS username',
                'glpi_users.id AS userid',
                'glpi_users.realname AS userrealname',
                'glpi_users.firstname AS userfirstname',
                'glpi_softwareversions.name AS version',
                'glpi_softwareversions.id AS vID',
                'glpi_softwareversions.softwares_id AS sID',
                'glpi_softwareversions.name AS vername',
                'glpi_entities.completename AS entity',
                'glpi_locations.completename AS location',
                'glpi_states.name AS state',
                'glpi_groups.name AS groupe',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_computers_softwareversions',
            'INNER JOIN' => [
                'glpi_softwareversions' => [
                    'ON' => [
                        'glpi_computers_softwareversions' => 'softwareversions_id',
                        'glpi_softwareversions' => 'id',
                    ],
                ],
                'glpi_computers' => [
                    'ON' => [
                        'glpi_computers_softwareversions' => 'computers_id',
                        'glpi_computers' => 'id',
                    ],
                ],
            ],
            'LEFT JOIN' => [
                'glpi_entities' => [
                    'ON' => [
                        'glpi_computers' => 'entities_id',
                        'glpi_entities' => 'id',
                    ],
                ],
                'glpi_locations' => [
                    'ON' => [
                        'glpi_computers' => 'locations_id',
                        'glpi_locations' => 'id',
                    ],
                ],
                'glpi_states' => [
                    'ON' => [
                        'glpi_computers' => 'states_id',
                        'glpi_states' => 'id',
                    ],
                ],
                'glpi_groups' => [
                    'ON' => [
                        'glpi_computers' => 'groups_id',
                        'glpi_groups' => 'id',
                    ],
                ],
                'glpi_users' => [
                    'ON' => [
                        'glpi_computers' => 'users_id',
                        'glpi_users' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                "glpi_softwareversions.$crit" => $ID,
                'glpi_computers.is_deleted' => 0,
                'glpi_computers.is_template' => 0,
            ],
            'ORDER' => ['version', 'compname'],
            'START' => 0,
            'LIMIT' => intval($_SESSION['glpilist_limit']),
        ];

        // Ajout de la restriction d'entités
        $entity_restrict = $dbu->getEntitiesRestrictRequest('', 'glpi_computers');
        if (!empty($entity_restrict)) {
            $query_params['WHERE'][] = new QueryExpression($entity_restrict);
        }

        $pdf->setColumnsSize(100);

        $result = $DB->request($query_params);
        if (($number = count($result)) > 0) {
            if ($number == $total) {
                $pdf->displayTitle('<b>' . sprintf(
                    __s('%1$s: %2$s'),
                    _sn('Installation', 'Installations', 2),
                    $number,
                ) . '</b>');
            } else {
                $pdf->displayTitle('<b>' . sprintf(
                    __s('%1$s: %2$s'),
                    _sn('Installation', 'Installations', 2),
                    $number . ' / ' . $total,
                ) . '</b>');
            }
            $pdf->setColumnsSize(8, 12, 10, 10, 12, 8, 10, 5, 17, 8);
            $pdf->displayTitle(
                '<b><i>' . _sn('Version', 'Versions', 2),
                __s('Name'),
                __s('Serial number'),
                __s('Inventory number'),
                __s('Location'),
                __s('Status'),
                __s('Group'),
                __s('User'),
                _sn('License', 'Licenses', 2),
                __s('Installation date') . '</i></b>',
            );

            foreach ($result as $data) {
                $compname = $data['compname'];
                if (empty($compname)) {
                    $compname = sprintf(__s('%1$s (%2$s)'), $compname, $data['cID']);
                }
                $lics = Item_SoftwareLicense::GetLicenseForInstallation(
                    'Computer',
                    $data['cID'],
                    $data['vID'],
                );

                $tmp = [];
                if (count($lics)) {
                    foreach ($lics as $lic) {
                        $licname = $lic['name'];
                        if (!empty($lic['type'])) {
                            $licname = sprintf(__s('%1$s (%2$s)'), $licname, $lic['type']);
                        }
                        $tmp[] = $licname;
                    }
                }
                $linkUser = User::canView();
                $pdf->displayLine(
                    $data['version'],
                    $compname,
                    $data['serial'],
                    $data['otherserial'],
                    $data['location'],
                    $data['state'],
                    $data['groupe'],
                    formatUserName(
                        $data['userid'],
                        $data['username'],
                        $data['userrealname'],
                        $data['userfirstname'],
                        $linkUser ? 1 : 0,
                    ),
                    implode(', ', $tmp),
                    Html::convDate($data['date_install']),
                );
            }
        } else {
            $pdf->displayTitle('<b>' . _sn('Installation', 'Installations', 2) . '</b>');
            $pdf->displayLine(__s('No item found'));
        }
        $pdf->displaySpace();
    }

    public static function pdfForVersionByEntity(PluginPdfSimplePDF $pdf, SoftwareVersion $version)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $softwareversions_id = $version->getField('id');

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . sprintf(
            __s('%1$s: %2$s'),
            Dropdown::getDropdownName(
                'glpi_softwares',
                $version->getField('softwares_id'),
            ),
            $version->getField('name'),
        ) . '</b>');
        $pdf->setColumnsSize(75, 25);
        $pdf->setColumnsAlign('left', 'right');

        $pdf->displayTitle('<b>' . __s('Entity'), _sn('Installation', 'Installations', 2) . '</b>');

        $lig = $tot = 0;
        if (in_array(0, $_SESSION['glpiactiveentities'])) {
            $nb = Item_SoftwareVersion::countForVersion($softwareversions_id, '0');
            if ($nb > 0) {
                $pdf->displayLine(__s('Root entity'), $nb);
                $tot += $nb;
                $lig++;
            }
        }
        $sql = ['SELECT' => ['id', 'completename'],
            'FROM'       => 'glpi_entities',
            'WHERE'      => $dbu->getEntitiesRestrictRequest('glpi_entities'),
            'ORDER'      => 'completename'];

        foreach ($DB->request($sql) as $ID => $data) {
            $nb = Item_SoftwareVersion::countForVersion($softwareversions_id, (string) $ID);
            if ($nb > 0) {
                $pdf->displayLine($data['completename'], $nb);
                $tot += $nb;
                $lig++;
            }
        }

        if ($tot > 0) {
            if ($lig > 1) {
                $pdf->displayLine(__s('Total'), $tot);
            }
        } else {
            $pdf->setColumnsSize(100);
            $pdf->setColumnsAlign('center');
            $pdf->displayLine(__s('No item to display'));
        }
        $pdf->displaySpace();
    }

    public static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $comp)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID = $comp->getField('id');

        // From Computer_SoftwareVersion::showForComputer();
        $query_params = [
            'SELECT' => [
                'glpi_softwares.softwarecategories_id',
                'glpi_softwares.name AS softname',
                'glpi_computers_softwareversions.id',
                'glpi_states.name AS state',
                'glpi_softwareversions.id AS verid',
                'glpi_softwareversions.softwares_id',
                'glpi_softwareversions.name AS version',
                'glpi_softwares.is_valid AS softvalid',
                'glpi_computers_softwareversions.date_install AS dateinstall',
            ],
            'FROM' => 'glpi_computers_softwareversions',
            'LEFT JOIN' => [
                'glpi_softwareversions' => [
                    'ON' => [
                        'glpi_computers_softwareversions' => 'softwareversions_id',
                        'glpi_softwareversions' => 'id',
                    ],
                ],
                'glpi_states' => [
                    'ON' => [
                        'glpi_states' => 'id',
                        'glpi_softwareversions' => 'states_id',
                    ],
                ],
                'glpi_softwares' => [
                    'ON' => [
                        'glpi_softwareversions' => 'softwares_id',
                        'glpi_softwares' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_computers_softwareversions.computers_id' => $ID,
                'glpi_computers_softwareversions.is_deleted' => 0,
            ],
            'ORDER' => ['softwarecategories_id', 'softname', 'version'],
        ];

        $output = [];

        $software_category = new SoftwareCategory();
        new SoftwareVersion();

        foreach ($DB->request($query_params) as $softwareversion) {
            $output[] = $softwareversion;
        }

        $installed = [];
        $pdf->setColumnsSize(100);
        $title = '<b>' . __s('Installed software') . '</b>';

        if (!count($output)) {
            $pdf->displayTitle(sprintf(__s('%1$s: %2$s'), $title, __s('No item to display')));
        } else {
            $title = sprintf(__s('%1$s: %2$s'), $title, count($output));
            $pdf->displayTitle($title);

            $cat = -1;
            foreach ($output as $soft) {
                if ($soft['softwarecategories_id'] != $cat) {
                    $cat = $soft['softwarecategories_id'];
                    $catname = $cat && $software_category->getFromDB($cat) ? $software_category->getName() : __s('Uncategorized software');

                    $pdf->setColumnsSize(100);
                    $pdf->displayTitle('<b>' . $catname . '</b>');

                    $pdf->setColumnsSize(39, 9, 11, 19, 14, 8);
                    $pdf->displayTitle(
                        '<b>' . __s('Name'),
                        __s('Status'),
                        __s('Version'),
                        __s('License'),
                        __s('Installation date'),
                        __s('Valid license') . '</b>',
                    );
                }

                // From Computer_SoftwareVersion::displaySoftsByCategory()
                $verid = $soft['verid'];
                $query_license_params = [
                    'SELECT' => [
                        'glpi_softwarelicenses.*',
                        'glpi_softwarelicensetypes.name AS type',
                    ],
                    'FROM' => 'glpi_computers_softwarelicenses',
                    'INNER JOIN' => [
                        'glpi_softwarelicenses' => [
                            'ON' => [
                                'glpi_computers_softwarelicenses' => 'softwarelicenses_id',
                                'glpi_softwarelicenses' => 'id',
                            ],
                        ],
                    ],
                    'LEFT JOIN' => [
                        'glpi_softwarelicensetypes' => [
                            'ON' => [
                                'glpi_softwarelicenses' => 'softwarelicensetypes_id',
                                'glpi_softwarelicensetypes' => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_computers_softwarelicenses.computers_id' => $ID,
                        'OR' => [
                            'glpi_softwarelicenses.softwareversions_id_use' => $verid,
                            [
                                'AND' => [
                                    'glpi_softwarelicenses.softwareversions_id_use' => 0,
                                    'glpi_softwarelicenses.softwareversions_id_buy' => $verid,
                                ],
                            ],
                        ],
                    ],
                ];

                $lic = '';
                foreach ($DB->request($query_license_params) as $licdata) {
                    $installed[] = $licdata['id'];
                    $lic .= (empty($lic) ? '' : ', ') . '<b>' . $licdata['name'] . '</b> ' . $licdata['serial'];
                    if (!empty($licdata['type'])) {
                        $lic = sprintf(__s('%1$s (%2$s)'), $lic, $licdata['type']);
                    }
                }

                $pdf->displayLine(
                    $soft['softname'],
                    $soft['state'],
                    $soft['version'],
                    $lic,
                    $soft['dateinstall'],
                    $soft['softvalid'],
                );
            } // Each version
        }

        // Affected licenses NOT installed
        $query_affected_params = [
            'SELECT' => [
                'glpi_softwarelicenses.*',
                'glpi_softwares.name AS softname',
                'glpi_softwareversions.name AS version',
                'glpi_states.name AS state',
            ],
            'FROM' => 'glpi_softwarelicenses',
            'LEFT JOIN' => [
                'glpi_computers_softwarelicenses' => [
                    'ON' => [
                        'glpi_computers_softwarelicenses' => 'softwarelicenses_id',
                        'glpi_softwarelicenses' => 'id',
                    ],
                ],
                'glpi_softwareversions' => [
                    'ON' => [
                        'OR' => [
                            [
                                'glpi_softwarelicenses' => 'softwareversions_id_use',
                                'glpi_softwareversions' => 'id',
                            ],
                            [
                                'AND' => [
                                    'glpi_softwarelicenses.softwareversions_id_use' => 0,
                                    [
                                        'glpi_softwarelicenses' => 'softwareversions_id_buy',
                                        'glpi_softwareversions' => 'id',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'glpi_states' => [
                    'ON' => [
                        'glpi_states' => 'id',
                        'glpi_softwareversions' => 'states_id',
                    ],
                ],
            ],
            'INNER JOIN' => [
                'glpi_softwares' => [
                    'ON' => [
                        'glpi_softwarelicenses' => 'softwares_id',
                        'glpi_softwares' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_computers_softwarelicenses.computers_id' => $ID,
            ],
        ];

        if (count($installed)) {
            $query_affected_params['WHERE'][] = new QueryExpression(
                'glpi_softwarelicenses.id NOT IN (' . implode(',', $installed) . ')',
            );
        }

        $req = $DB->request($query_affected_params);
        if ($req->numrows()) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>' . __s('Affected licenses of not installed software', 'pdf') . '</b>');

            $pdf->setColumnsSize(50, 13, 13, 24);
            $pdf->displayTitle('<b>' . __s('Name'), __s('Status'), __s('Version'), __s('License') . '</b>');

            $lic = '';
            foreach ($req as $data) {
                $lic .= '<b>' . $data['name'] . '</b> ' . $data['serial'];
                if (!empty($data['softwarelicensetypes_id'])) {
                    $lic = sprintf(
                        __s('%1$s (%2$s)'),
                        $lic,
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_softwarelicensetypes',
                            $data['softwarelicensetypes_id'],
                        )),
                    );
                }
                $pdf->displayLine($data['softname'], $data['state'], $data['version'], $lic);
            }
        }

        $pdf->displaySpace();
    }
}
