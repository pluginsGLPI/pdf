<?php

class PluginPdfItem_TicketModel extends PluginPdfItem_Ticket 
{
   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $ticket, $sub=false)
   {
      global $DB;

      $dbu = new DbUtils();

      $instID = $ticket->fields['id'];

      if (!$ticket->can($instID, READ)) {
         return false;
      }

      $result = $DB->request('glpi_items_tickets',
                             ['SELECT'    => 'itemtype',
                              'DISTINCT'  => true,
                              'WHERE'     => ['tickets_id' => $instID],
                              'ORDER'     => 'itemtype']);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Item', 'Items', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, '');
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(14, 14, 18, 12, 12, 12, 18);
         $pdf->displayTitle("<i>".__('Type'), __('Name'), __('Model'), __('Serial number'),
                                  __('Inventory number'), __('Location'), __('Comments')."</i>");

                                        $totalnb = 0;
         foreach ($result as $row) {
            $itemtype = $row['itemtype'];
            if (!($item = $dbu->getItemForItemtype($itemtype))) {
               continue;
            }

            if ($item->canView()) {
               $itemtable = $dbu->getTableForItemType($itemtype);

               $model_name = substr($itemtable, 5, -1).'models';

               $query = [
                  "SELECT" => [
                     "`$itemtable`" => ["*"],
                     "`glpi_items_tickets`" => ["`id` AS `IDD`"],
                     "`glpi_entities`" => ["`id` AS `entity`"]
                  ],
                  "FROM" => [
                     "`$itemtable`"
                  ]
               ];

               if ($itemtype != "Entity") {
                  $query["LEFT JOIN"] = [
                     "`glpi_entities`" => [
                        "ON" => [
                           "`$itemtable`" => "`entities_id`",
                           "`glpi_entities`" => "`id`"
                        ]
                     ]
                  ];
               }

               $query["INNER JOIN"] = [
                  "`glpi_items_tickets`" => [
                     "ON" => [
                        "`$itemtable`" => "`id`",
                        "`glpi_items_tickets`" => "`items_id`"
                     ]
                  ]
               ];

               $query["WHERE"] = [
                  "`glpi_items_tickets`.`itemtype`" => "$itemtype",
                  "`glpi_items_tickets`.`tickets_id`" => "$instID"
               ];

               if ($item->maybeTemplate()) {
                  $query["WHERE"]["AND"]["`$itemtable`.is_template"] = "0";
               }

               $query["ORDER"] = [
                  "`glpi_entities`.`completename`",
                  "`$itemtable`.`name`"
               ];
               

               $result_linked = $DB->request($query);
               $nb            = count($result_linked);

               $prem = true;
               foreach ($result_linked as $data) {
                  $name = $data["name"];
                  if (empty($data["name"])) {
                     $name = "(".$data["id"].")";
                  }
                  if (isset($data[$model_name."_id"])) {
                     $model = Dropdown::getDropdownName("glpi_".$model_name, $data[$model_name."_id"]);
                  } else {
                     $model = "";
                  }
                  if (isset($data["locations_id"])) {
                     $location = Dropdown::getDropdownName("glpi_locations", $data["locations_id"]);
                  } else {
                     $location = "";
                  }
                  if ($prem) {
                     $typename = $item->getTypeName($nb);
                     $pdf->displayLine(Toolbox::stripTags(sprintf(__('%1$s: %2$s'), $typename, $nb)),
                                       Toolbox::stripTags($name),
                                       Toolbox::stripTags($model),
                                       isset($data["serial"])?Toolbox::stripTags($data["serial"]):'',
                                       isset($data["otherserial"])?Toolbox::stripTags($data["otherserial"]):'',
                                       Toolbox::stripTags($location),
                                       Toolbox::stripTags($data['comment']??''),$nb);
                  } else {
                     $pdf->displayLine('',
                                       Toolbox::stripTags($name),
                                       Toolbox::stripTags($model),
                                       isset($data["serial"])?Toolbox::stripTags($data["serial"]):'',
                                       isset($data["otherserial"])?Toolbox::stripTags($data["otherserial"]):'',
                                       Toolbox::stripTags($location),
                                       Toolbox::stripTags($data['comment']??''),$nb);
                  }
                  $prem = false;
               }
               $totalnb += $nb;
            }
         }
         $pdf->displayLine("<b><i>".sprintf(__('%1$s = %2$s')."</b></i>", __('Total'), $totalnb));
      }
      $pdf->displaySpace();
   }
}
