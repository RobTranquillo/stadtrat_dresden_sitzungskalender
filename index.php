<?php 
    ini_set('display_errors',true);
    error_reporting(E_ALL);
?>
<html>

<h1>Sitzungsplan Stadtrat Dresden</h1>

Mit den hier veröffentlichten links können alle kommenden Sitzungen des Dresdner Stadtrats 
und seiner Gremien als Termin in ihrem elektronischen Kalender abboniert werden. 
Diese werden täglich aktualisiert.


<div id="icallist">
    <ul>
       <?php
            printAllDates();
        ?>
    <ul>
 <div>
</html>

<?php
function printAllDates()
{
   $json_dates = file_get_contents('icals_paths.json');
   $dates = json_decode( $json_dates );
   foreach( $dates AS $date )
   {       
        echo "<li><a href='$date->url'> $date->name </a>";
   }
}
?>