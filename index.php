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
            printAllIcal();
        ?>
    <ul>
 <div>
</html>

<?php
function printAllIcal()
{
   $json_icals = file_get_contents('icals_paths.json');
   $icals = json_decode( $json_icals );
   foreach( $icals AS $ical)
   {
        $ical_str = substr($ical, strrpos($ical,'/')+1);
        echo "<li><a href='$ical'> $ical_str </a>";
   }
}
?>
