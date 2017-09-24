<?php
defined('_JEXEC') or die;

echo '<div id="chart1"></div><hr>'.
    '<div id="chart2"></div><hr>'.
    '<p>Exporter les reservations faites du '.
    '<input type="text" name="debut" class="dp" id="exportBegin" value="2017-01-01" style="width:100px"/>'.
    ' au <input type="text" name="fin" class="dp" id="exportEnd" value="2017-12-31" style="width:100px"/>.</p>'.
    '<input type="submit" class="exportBtn" value="export" id="exportDb"/>';

?>