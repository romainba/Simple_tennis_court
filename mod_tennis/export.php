<?php

class ModTennisExporter {
    
    public static function exportDb($begin, $end)
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

          $u = sizeof($users) + 1;
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('user_id',
          	$objPHPExcel->getActiveSheet(), 'A2:A'.$u));

          /* get reservation table */
          $header = array(
              array('user1', 15),
              array('user2', 15),
              array('date', 20),
              array('type', 20),
          );
          
          $query = $db->getQuery(true);
          $query->select(array('a.user1', 'a.user2', 'a.date', 'b.name'))
                ->from($db->quoteName('#__reservation', 'a'))
                ->join('INNER', $db->quoteName('#__res_type', 'b') . ' on (' .
                $db->quoteName('a.type') . ' = ' . $db->quoteName('b.id') . ')')
                ->order($db->quoteName('date').' ASC')
                ->where($db->quoteName('date') . '>=' . $db->quote($begin) . ' and ' .
                $db->quoteName('date') . '<=' . $db->quote($end));

          //return $query->__tostring();
          
          $db->setQuery($query);
          $res = $db->loadAssocList();

          $workSheet = new PHPExcel_Worksheet($objPHPExcel, 'reservation');
          $objPHPExcel->addSheet($workSheet, 1);
          
          foreach($header as $h => $v) {
              $c = chr(ord('A') + $h);
              $objPHPExcel->setActiveSheetIndex(1)->getColumnDimension($c)->setWidth($v[1]);
          }

          $n = sizeof($res);
          $dataCell = $n + 3;
              
          $v = explode('-', $begin);
          $b = array(intval($v[0]), intval($v[1]));
          $v = explode('-', $end);
          $e = array(intval($v[0]), intval($v[1]));
          
          $data = array(
              array('num res', $numRes),
              array('from', $begin, 'to', $end),
          );

          $t = array();
          array_push($t, array('Mois', 'Normal', 'Cours', 'Manifestation'));
          $i = $dataCell + 5;
          $n++;
          for ($m = $b[0]*12 + $b[1]; $m <= $e[0]*12 + $e[1]; $m++) {
              $d = date("Y-m-d", mktime(0, 0, 0, $m % 12, 1, $m/12));
              $v1 = '=COUNTIFS(cx,">="&A'.$i.',cx,"<="&EOMONTH(A'.$i.', 0),dx,"=normal")';
              $v2 = '=COUNTIFS(cx,">="&A'.$i.',cx,"<="&EOMONTH(A'.$i.', 0),dx,"=cours de tennis")';
              $v3 = '=COUNTIFS(cx,">="&A'.$i.',cx,"<="&EOMONTH(A'.$i.', 0),dx,"=manifestation")';
              array_push($t, array($d, $v1, $v2, $v3));
              $i++;
          }
          
          $objPHPExcel->setActiveSheetIndex(1)
                      ->fromArray(array_column($header, 0), NULL, 'A1')
                      ->fromArray($res, NULL, 'A2')
                      ->fromArray($data, NULL, 'A' . $dataCell)
                      ->fromArray($t, NULL, 'A' . ($dataCell + 4));
          
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('begin',
          	$objPHPExcel->getActiveSheet(), 'B'.($dataCell + 1)));
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('end',
          	$objPHPExcel->getActiveSheet(), 'D'.($dataCell + 1)));
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('ax',
          	$objPHPExcel->getActiveSheet(), 'A2:A'.$n) );
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('bx',
          	$objPHPExcel->getActiveSheet(), 'B2:B'.$n) );
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('cx',
          	$objPHPExcel->getActiveSheet(), 'C2:C'.$n) );
          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('dx',
          	$objPHPExcel->getActiveSheet(), 'D2:D'.$n) );

          /* # reservation per users */
          $t = array();
          array_push($t, array('#res'));
          for ($j = 2; $j <= $u; $j++) {
              $v = '=COUNTIFS(ax,"="&A'.$j.',cx,">="&begin,cx,"<="&end)+'.
                  'COUNTIFS(bx,"="&A'.$j.',cx,">="&begin,cx,"<="&end)';
              array_push($t, array($v));
          }

          $objPHPExcel->setActiveSheetIndex(0)
                      ->fromArray($t, NULL, 'O1');

          $objPHPExcel->addNamedRange( new PHPExcel_NamedRange('ox',
          	$objPHPExcel->getActiveSheet(), 'O2:O'.$u));

          /* activity histogram */
          $histo = array(0, 1, 5, 10, 20, 50);
          $t = array();
          array_push($t, array('#res', '#player'));
          $i = $i + 2;
          $n = $i + 1;
          foreach ($histo as $h) {
              $v = '=COUNTIFS(ox,"="&A'.$n.')';
              array_push($t, array($h, $v));
              $n++;
          }
          $objPHPExcel->setActiveSheetIndex(1)
                      ->fromArray($t, NULL, 'A'.$i);

          $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
          ob_start();
          $objWriter->save("php://output");
          $xlsData = ob_get_contents();
          ob_end_clean();
          $response = array(
              'file' => "data:application/vnd.ms-excel;base64,".base64_encode($xlsData)
          );

          return $response;
    }
}

?>