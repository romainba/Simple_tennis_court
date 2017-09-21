<?php
defined('_JEXEC') or die;

echo '<div id="chart1"></div><hr>'.
    '<div id="chart2"></div><hr>'.
    '<p>Exporter les reservations faites du '.
    '<input type="text" name="debut" class="dp" id="exportBegin" style="width:80px"/>'.
    ' au <input type="text" name="fin" class="dp" id="exportEnd" style="width:80px"/>.</p>'.
    '<input type="submit" class="exportBtn" value="exporter" id="exportDb"/>';

?>