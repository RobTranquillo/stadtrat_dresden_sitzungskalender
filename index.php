<?php 
    #ini_set('display_errors',true);
    #error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stadtrat Dresden Kalender</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

	<div class="container">
		<div class="jumbotron">
					<h1>Sitzungsplan Stadtrat Dresden</h1>
					<p>Mit den hier veröffentlichten links können alle kommenden Sitzungen des Dresdner Stadtrats 
					und seiner Gremien als Termin in ihrem elektronischen Kalender abboniert werden. 
					Diese werden täglich aktualisiert.</p>
		</div>
		<div class="panel panel-default">
                <div class="panel-body">
                  zuletzt aktualisiert: heute
                </div>
		</div>

		<div id="icallist">
			<ul>
			   <?php
					printAllDates();
				?>
			<ul>
		</div>
	</div>
  </body>
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