<html>

<h1>Sitzungsplan Stadtrat Dresden</h1>

Mit den hier veröffentlichten links können sie sich alle kommenden Sitzungen des Dresdner Stadtrats und seiner Gremien als Termin in ihren elektonischen Kalender automatisch eintragen lassen. Diese werden täglich aktualisiert.

<h1> ical: </h1>
<ul>
<li> <a href="boogie.eltanin.uberspace.de/staDDrat/ical/Stadtrat.ical"> Stadrat Dresden </a> </li>
<li> <a href="boogie.eltanin.uberspace.de/staDDrat/ical/Ausländerbeirat.ical"> Ausländerbeirat </a> </li>
</ul>


<div id="icallist">
 <?php
    printAllIcal();
 ?>
<div>
</html>

<?php
function printAllIcal()
{
   $json_icals = file_get_contents('icals.json');
   $icals = json_decode( $json_icals );
   print_r($icals);
}
?>
