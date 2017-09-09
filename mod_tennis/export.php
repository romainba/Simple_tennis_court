<?php

function co($c, $i)
{
    return chr(ord($c) + $i);
}

class ModTennisExporter {

    public static function newBarChart($title, $col, $row, $numCol, $numRow)
    {
        $sl = array();
        for ($c = 1; $c < $numCol; $c++)
            array_push($sl, new PHPExcel_Chart_DataSeriesValues('String',
            'reservation!'.co($col, $c).$row, NULL, 1));
        
        $sx = array(
            new PHPExcel_Chart_DataSeriesValues('String',
            'reservation!'.$col.($row+1).':'.$col.($row+$numRow-1),
            NULL, $numRow-1));
        
        $sy = array();
        for ($c = 1; $c < $numCol; $c++)
            array_push($sy, new PHPExcel_Chart_DataSeriesValues('String',
            'reservation!'.co($col, $c).($row+1).':'.co($col, $c).($row+$numRow-1),
            NULL, $numRow-1));

        $ds = new PHPExcel_Chart_DataSeries(
            PHPExcel_Chart_DataSeries::TYPE_BARCHART,
            PHPExcel_Chart_DataSeries::GROUPING_CLUSTERED,
            range(0, count($sy)-1),
            $sl, $sx, $sy
        );
        
        $pa = new PHPExcel_Chart_PlotArea(NULL, array($ds));
        if ($numCol > 1)
            $legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, NULL, false);
        $title = new PHPExcel_Chart_Title($title);
        $xal = new PHPExcel_Chart_Title('#reservations');
        $yal = new PHPExcel_Chart_Title('#groupes');
        
        return new PHPExcel_Chart(
            'histo_group',
            $title,
            $legend,
            $pa,
            true,			// plotVisibleOnly
            0,			// displayBlanksAs
            $xal, $yal
        );
    }

    
    public static function exportDb($begin, $end)
    {
          require_once dirname(__FILE__) . '/Classes/PHPExcel.php';
          require_once dirname(__FILE__) . '/const.php';

          $charHeight = 20;
          
          $o = new PHPExcel();
          $o->getProperties()->setCreator("TCLV")
                      ->setLastModifiedBy("")
                      ->setTitle("TCLV users")
                      ->setSubject("")
                      ->setDescription("")
                      ->setKeywords("")
                      ->setCategory("");

          PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
          
          $db = &JFactory::getDbo();
          $query = $db->getQuery(true);

          $s1 = array(
              'fill' => array(
                  'type' => PHPExcel_Style_Fill::FILL_SOLID,
                  'color' => array('argb' => '00f0f000')
              ),
          );
          $s2 = array(
              'borders' => array(
                  'allborders' => array(
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
                      'color' => array('argb' => 'FF202020'),
                  ),
              ),
          );
          
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
                ->order($db->quoteName('a.id').' ASC');

          $db->setQuery($query);
          $users = $db->loadAssocList();

          $wu = new PHPExcel_Worksheet($o, 'users');
          $o->addSheet($wu, 0);

          foreach($header as $h => $v) {
              $c = co('A', $h);
              $wu->getColumnDimension($c)->setWidth($v[2]);
          }

          /* get group table */
          $query = $db->getQuery(true);
          $query->select($db->quoteName('group_id'))
                ->from($db->quoteName('#__users'))
                ->group($db->quoteName('group_id'))
                ->order($db->quoteName('group_id').' ASC');
          $db->setQuery($query);
          $groups = $db->loadAssocList();

          $h = array('group', '# res');
          $u = sizeof($users) + 1;
          $gh = $u + 3;
          $g = $gh + 1;
          
          for ($i = 0; $i < sizeof($groups); $i++, $g++) {
              $v = '=COUNTIFS(ex,"="&A'.$g.')+COUNTIFS(fx,"="&A'.$g.')';
              array_push($groups[$i], $v);
          }
          $g--;

          $wu->fromArray(array_column($header, 0), NULL, 'A1')
             ->fromArray($users, NULL, 'A2')
             ->fromArray($h, NULL, 'A'.$gh)
             ->fromArray($groups, NULL, 'A'.($gh + 1));
          
          $o->addNamedRange(new PHPExcel_NamedRange('user_id', $wu, 'A2:A'.$u));
          $o->addNamedRange(new PHPExcel_NamedRange('user_table', $wu, 'A2:O'.$u));
          $o->addNamedRange(new PHPExcel_NamedRange('groups', $wu, 'A'.($gh+1).':A'.$g));
          $o->addNamedRange(new PHPExcel_NamedRange('res_group', $wu, 'B'.($gh+1).':B'.$g));
         
          /* get reservation table */
          $header = array(
              array('joueur 1', 15),
              array('joueur 2', 15),
              array('date', 20),
              array('type', 20),
              array('groupe 1', 15),
              array('groupe 2', 15),
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

          $w = new PHPExcel_Worksheet($o, 'reservation');
          $o->addSheet($w, 1);
          
          foreach($header as $h => $v) {
              $c = co('A', $h);
              $w->getColumnDimension($c)->setWidth($v[1]);
          }

          $n = sizeof($res);
          $dataCell = $n + 3;
          for ($i = 0; $i < $n; $i++) {
              array_push($res[$i], '=vlookup(A'.($i+1).', user_table, 9)');
              array_push($res[$i], '=vlookup(B'.($i+1).', user_table, 9)');
          }
          
          $v = explode('-', $begin);
          $b = array(intval($v[0]), intval($v[1]));
          $v = explode('-', $end);
          $e = array(intval($v[0]), intval($v[1]));
          
          $data = array(
              array('nombre de reservation', '', $n),
              array('De', $begin, 'a', $end),
              array(),
              array(),
              array('Nombre reservation par mois:')
          );
          
          $t = array();
          array_push($t, array('Mois', 'Normal', 'Cours', 'Manifestation'));
          $i = $dataCell + 5;
          $n++;
          for ($m = $b[0]*12 + $b[1]; $m <= $e[0]*12 + $e[1]; $m++) {
              $d = date("Y-m-d", mktime(0, 0, 0, $m % 12, 1, $m/12));
              $i++;
              $v1 = '=COUNTIFS(cx,">="&A'.$i.',cx,"<="&EOMONTH(A'.$i.', 0),dx,"=normal")';
              $v2 = '=COUNTIFS(cx,">="&A'.$i.',cx,"<="&EOMONTH(A'.$i.', 0),dx,"=cours de tennis")';
              $v3 = '=COUNTIFS(cx,">="&A'.$i.',cx,"<="&EOMONTH(A'.$i.', 0),dx,"=manifestation")';
              array_push($t, array($d, $v1, $v2, $v3));
          }
          
          $w->fromArray(array_column($header, 0), NULL, 'A1')
            ->fromArray($res, NULL, 'A2')
            ->fromArray($data, NULL, 'A' . $dataCell)
            ->fromArray($t, NULL, 'A' . ($dataCell + 5));

          $w->getStyle('A1:D1')->applyFromArray($s1);
          $w->getStyle('A1:D'.$n)->applyFromArray($s2);
          $w->getStyle('A'.($dataCell+5).':D'.($dataCell+5))->applyFromArray($s1);
          $w->getStyle('A'.($dataCell+5).':D'.$i)->applyFromArray($s2);
          
          $o->addNamedRange(new PHPExcel_NamedRange('begin', $w, 'B'.($dataCell + 1)));
          $o->addNamedRange(new PHPExcel_NamedRange('end', $w, 'D'.($dataCell + 1)));
          $o->addNamedRange(new PHPExcel_NamedRange('ax', $w, 'A2:A'.$n) );
          $o->addNamedRange(new PHPExcel_NamedRange('bx', $w, 'B2:B'.$n) );
          $o->addNamedRange(new PHPExcel_NamedRange('cx', $w, 'C2:C'.$n) );
          $o->addNamedRange(new PHPExcel_NamedRange('dx', $w, 'D2:D'.$n) );
          $o->addNamedRange(new PHPExcel_NamedRange('ex', $w, 'E2:E'.$n) );
          $o->addNamedRange(new PHPExcel_NamedRange('fx', $w, 'F2:F'.$n) );

          $chart = ModTennisExporter::newBarChart(
              $data[4][0], 'A', $dataCell+5, 4, $i - $dataCell - 4);
          $cp = $dataCell + 5;
          $chart->setTopLeftPosition('F'.$cp)
                ->setBottomRightPosition('N'.($cp + $charHeight));
          $w->addChart($chart);
          $cp += $charHeight + 2;

          /* # reservation per users */
          $t = array();
          array_push($t, array('#res'));
          for ($j = 2; $j <= $u; $j++) {
              $v = '=COUNTIFS(ax,"="&A'.$j.')+COUNTIFS(bx,"="&A'.$j.')';
              array_push($t, array($v));
          }

          $wu->fromArray($t, NULL, 'O1');
          $o->addNamedRange(new PHPExcel_NamedRange('ox', $wu, 'O2:O'.$u));
      
          $wu->setAutoFilter('A1:O'.$u)
             ->getStyle('A1:O1')->applyFromArray($s1);
          $wu->getStyle('A1:O'.$u)->applyFromArray($s2);
          $wu->getStyle('A'.$gh.':B'.$gh)->applyFromArray($s1);
          $wu->getStyle('A'.$gh.':B'.$g)->applyFromArray($s2);

          /* activity histogram par joueurs */
          $histo = array(0, 1, 5, 10, 20, 50);
          $t = array();
          array_push($t, array('Histogramme du nombre de joueur par nombre de reservation:'));
          array_push($t, array('# reserverations', '# joueurs'));
          $i = $i + 2;
          $n = $i + 1;
          foreach ($histo as $h) { 
              $n++;
              if ($n == $i + 2)
                  $v = '=COUNTIFS(ox,"="&A'.$n.')';
              else
                  $v = '=COUNTIFS(ox,">"&A'.($n-1).',ox,"<="&A'.$n.')';
              array_push($t, array($h, $v));
          }
          $w->fromArray($t, NULL, 'A'.$i);
          $i++;
          $w->getStyle('A'.$i.':B'.$i)->applyFromArray($s1);
          $w->getStyle('A'.$i.':B'.$n)->applyFromArray($s2);

          $chart = ModTennisExporter::newBarChart($t[0][0], 'A', $i, 2, sizeof($histo) + 1);
          $chart->setTopLeftPosition('F'.$cp)
                ->setBottomRightPosition('N'.($cp + $charHeight));
          $w->addChart($chart);
          $cp += $charHeight + 2;

          /* activity histogram per groups */
          $histo = array(0, 1, 5, 10, 20, 50);
          $t = array();
          array_push($t, array('Histogramme du nombre de groupes par nombre de reservation:'));
          array_push($t, array('# reserverations', '# groupes'));
          $i = $n + 3;
          $n = $i + 1;
          foreach ($histo as $h) {
              $n++;
              if ($n == $i + 2) 
                  $v = '=COUNTIFS(res_group,"="&A'.$n.')';
              else
                  $v = '=COUNTIFS(res_group,">"&A'.($n-1).',res_group,"<="&A'.$n.')';
              array_push($t, array($h, $v));
          }
          $w->fromArray($t, NULL, 'A'.$i);
          $i++;
          $w->getStyle('A'.$i.':B'.$i)->applyFromArray($s1);
          $w->getStyle('A'.$i.':B'.$n)->applyFromArray($s2);

          $chart = ModTennisExporter::newBarChart($t[0][0], 'A', $i, 2, sizeof($histo) + 1);
          $chart->setTopLeftPosition('F'.$cp)
                ->setBottomRightPosition('N'.($cp + $charHeight));
          $w->addChart($chart);

          $objWriter = new PHPExcel_Writer_Excel2007($o);
          ob_start();
          $objWriter->setIncludeCharts(TRUE);
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