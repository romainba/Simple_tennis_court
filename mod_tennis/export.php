<?php

class ModTennisExporter {
    
    public static function exportDb()
    {
          require_once dirname(__FILE__) . '/Classes/PHPExcel.php';
          require_once dirname(__FILE__) . '/const.php';
          
          $objPHPExcel = new PHPExcel();
          $objPHPExcel->getProperties()->setCreator("TCLV")
                      ->setLastModifiedBy("")
                      ->setTitle("TCLV users")
                      ->setSubject("")
                      ->setDescription("")
                      ->setKeywords("")
                      ->setCategory("");

          PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
          
          $db = &JFactory::getDbo();
          $query = $db->getQuery(true);

          /* get user table */
          $header = array(
              array('id', 'a.id', 8),
              array('name', 'a.name', 30),
              array('username', 'a.username', 30),
              array('email', 'a.email', 30),
              array('block', 'a.block', 6),
              array('requireReset', 'a.requireReset', 6),
              array('lastvisitDate', 'a.lastvisitDate', 20),
              array('naissance', 'a.naissance', 15),
              array('group_id', 'a.group_id', 12),
              array('abonnement', 'b.names', 15),
              array('address', 'a.address', 25),
              array('city', 'a.city', 25),
              array('phone', 'a.phone', 20)
          );

          $query->select($db->quoteName(array_column($header, 1)))
                ->from($db->quoteName('#__users', 'a'))
                ->join('INNER', $db->quoteName('#__abo_type', 'b') .
                ' on (' . $db->quoteName('a.abonnement') . ' = ' . $db->quoteName('b.id') . ')')
                ->order($db->quoteName('a.name').' ASC');

          $db->setQuery($query);
          $users = $db->loadAssocList();

          $workSheet = new PHPExcel_Worksheet($objPHPExcel, 'users');
          $objPHPExcel->addSheet($workSheet, 0);

          foreach($header as $h => $v) {
              $c = chr(ord('A') + $h);
              $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($c)->setWidth($v[2]);
          }
  
          $objPHPExcel->setActiveSheetIndex(0)
                      ->fromArray(array_column($header, 0), NULL, 'A1')
                      ->fromArray($users, NULL, 'A2');

          /* get reservation table */
          $header = array(
              array('user1', 10),
              array('user2', 10),
              array('date', 20),
              array('type', 20),
          );
          
          $query = $db->getQuery(true);
          $query->select(array('a.user1', 'a.user2', 'a.date', 'b.name'))
                ->from($db->quoteName('#__reservation', 'a'))
                ->join('INNER', $db->quoteName('#__res_type', 'b') . ' on (' .
                $db->quoteName('a.type') . ' = ' . $db->quoteName('b.id') . ')')
                ->order($db->quoteName('date').' ASC');
          $db->setQuery($query);
          $res = $db->loadAssocList();

          $workSheet = new PHPExcel_Worksheet($objPHPExcel, 'reservation');
          $objPHPExcel->addSheet($workSheet, 1);
          
          foreach($header as $h => $v) {
              $c = chr(ord('A') + $h);
              $objPHPExcel->setActiveSheetIndex(1)->getColumnDimension($c)->setWidth($v[1]);
          }

          $objPHPExcel->setActiveSheetIndex(1)
                      ->fromArray(array_column($header, 0), NULL, 'A1')
                      ->fromArray($res, NULL, 'A2');
          
          $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
          ob_start();
          $objWriter->save("php://output");
          $xlsData = ob_get_contents();
          ob_end_clean();
          $response = array(
              'file' => "data:application/vnd.ms-excel;base64,".base64_encode($xlsData)
          );

          // =COUNTIFS($C$2:$C$19,">="&G3,$C$2:$C$19, "<="&EOMONTH(G3, 0))
          
          return $response;
    }
}

?>