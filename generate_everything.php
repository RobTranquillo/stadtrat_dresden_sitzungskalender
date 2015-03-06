<?php
$url = 'http://ratsinfo.dresden.de/gr0040.php';
$dl_folder = "./pages/";
$ical_folder = "./ical/";

#download_all_overviews( $url, $dl_folder );
$all_dates = scrape_files( $dl_folder );

//write ical file
$paths = build_ical($all_dates, $ical_folder);
persist_paths($paths);



######################
# adds the array with the paths as json behind 
# the ical files hiself for a static access from index.php
function persist_paths($paths)
{
    if(count($paths) > 0 ) 
    {
        file_put_contents("icals_paths.json", json_encode($paths));
    }
}

######################
# create the icalendars from a array
# writes one file for ervery commitee
# and one file for all commitees   
function build_ical( $all_dates , $ical_folder )
{
    if( is_dir($ical_folder) == false ) mkdir($ical_folder);
    
    function getIcalDate($time)
    {
        return date('Ymd\THis', $time);
    }

    $paths = array();
    $out = '';
    foreach( $all_dates AS $committee )
    {
        $out = 'BEGIN:VCALENDAR'.
                "\n".'VERSION:2.0'.
                "\n".'PRODID:https://github.com/RobTranquillo/stadtrat_dresden_sitzungsplan'.
                "\n".'METHOD:PUBLISH'
                ;
        
        foreach( $committee['dates'] AS $session )
        {
            $out .= "\n".'BEGIN:VEVENT'.
                    "\n".'UID:'.md5( $committee['committee'].$session['date'] ).
                    "\n".'SUMMARY:'.$committee['committee'].
                    "\n".'DTSTART:'.getIcalDate( $session['date'] ).
                    "\n".'DTEND:'.getIcalDate( $session['date'] + 7200 ).
                    "\n".'DTSTAMP:'.getIcalDate( mktime() ).
                    "\n".'DESCRIPTION:'.$committee['committee'].
                    "\n".'LOCATION:'. html_entity_decode( $session['location'] ).
                    "\n".'ORGANIZER:CN="Rob Tranquillo, http://offenesdresden.de":MAILTO:rob.tranquillo@gmx.de'.
                    "\n".'CLASS:PUBLIC'.
                    "\n".'END:VEVENT'
                    ;
        }
        $out .= "\nEND:VCALENDAR";
        
        $filename = $ical_folder . $committee['filename'] .'.ics';
        if( file_put_contents( $filename, $out ) != false )
            array_push($paths, array('url'=>$filename, 'name'=>$committee['committee']));
    }

    if( file_put_contents( $ical_folder.'alle.ics', $out ) != false )
        array_push($paths, array( 'url' => $ical_folder.'alle.ics', 'name' => 'Alle Gremien zusammen'));

    return $paths;
}

    
######################
# gets all session dates from the files in 
function scrape_files()
{
    $folder = 'pages/';
    $files = scandir($folder);
    $files = array_slice($files, 2); //cut away . and .. 
    date_default_timezone_set('Europe/Berlin');
    $all_dates = array();

    foreach( $files AS $file)
    {
        $html = file_get_contents( $folder.$file );
        $committee = get_comm($html);
        $end = 0;
        $dates = array();
        
        while(true)
        {
            $start = strpos($html, 'smc_td smc_field_silink', $end);
            if( $start === false ) break;
            $end = strpos($html, '</td>', $start);
            if( $end === false ) break;
            $start = strpos($html,'>',$start)+1;
            $td_date = substr($html, $start, $end-$start);
            $td_loca = substr($html, $end+5);

            //dates with links are shurly in the past and all next dates in th document also
            if( substr_count( $td_date, '<a ' )) break; 

            //clean line from SMCINFO-tag
            $dateStr = trim( substr( $td_date, 0, strpos($td_date,'<!')));
            $timeStr = substr( $td_date, strpos($td_date,'->')+2);
            $timeStr = explode('-', $timeStr );  $timeStr = trim($timeStr[0]);  //sometimes the date is served in from-to Format, so we only want the starttime 
        
            // convert to unix timestamp
            $date = explode('.', $dateStr);
            $time = explode(':', $timeStr);
            $uxts = mktime( $time[0],$time[1],0,$date[1],$date[0],$date[2] );
            if( date("d.m.Y H:i", $uxts) != $dateStr.' '.$timeStr) echo ' error while getting date! ';

            //get location
            $location = substr($td_loca, 4, stripos($td_loca, '</td>')-4 );
            $location = str_replace('<br />','\n', $location);
            $location = str_replace("\n",'', $location);

            array_push( $dates, array('date'=>$uxts, 'location'=>$location ) );
        }
        
        array_push( $all_dates, array( 'committee'=> $committee, 'dates' => $dates, 'filename' => $file ) );
    }

    if(count($all_dates) > 0 ) return $all_dates;
    else return false;
}



######################
# extract the committee name 
function get_comm( $str )
{
    $start = stripos( $str, '<title>' ) + 7;
    $end = stripos( $str, '</title>', $start );
    $str = html_entity_decode( substr( $str, $start, $end - $start ) );
    
    return trim( $str );
}
    
    
######################
# Main Step one
function download_all_overviews( $url , $folder_path )
{
    $html = file_get_contents( $url );
    $committee = array();

    $tab_start  = strpos( $html , '<table ');
    $tab_end    = strpos( $html , '</table>', $tab_start);
    $table      = substr($html, $tab_start , $tab_end - $tab_start);

    $end =  0;
    while ( true )
    {
        $start = strpos( $table, '<tr ', $end);
        if( $start === false ) break;
    
        $end    = strpos( $table, '</tr>', $start);
        if( $end === false ) break;

        $tr = substr($table, strpos($table,'>',$start), $end-$start);
        $tds = get_tds( $tr );

        $committee_name_start = strpos($tds[0],'>', strpos($tds[0], '<a '));
        $committee_name_end = strpos($tds[0],'</a>', $committee_name_start) - 1;
        $committee_name = substr($tds[0], $committee_name_start+1, $committee_name_end - $committee_name_start);
        $committee_name = html_entity_decode( trim( str_replace('/', '_', $committee_name) ) );
        if( $committee_name == '' || substr_count($committee_name,'<!--S') > 0) continue;

        $sessions_cal_start = strpos($tds[3],'href='); 
        if( $sessions_cal_start !== false)         
        {
            $sessions_cal_start = $sessions_cal_start + 6;
            $sessions_cal_end = strpos($tds[3],'"', $sessions_cal_start);
            $sessions_cal_link = substr($tds[3], $sessions_cal_start, $sessions_cal_end - $sessions_cal_start); 
            $sessions_cal_link = 'http://ratsinfo.dresden.de/' . html_entity_decode($sessions_cal_link);
            array_push( $committee, $sessions_cal_link );
        }
    }

    ## last step: download every single sessions page
    foreach( $committee as  $link ) download_sessions( $link , $folder_path);
    
}


######################
function download_sessions( $link , $savepath)
{
    $handle = fopen( $link , "rb");
    $contents = stream_get_contents($handle);
    fclose($handle);

    if( is_dir($savepath) == false ) mkdir( $savepath );
    $filename = $savepath . 'Gremium_'. substr($link, strripos($link,'=')+1);
    echo "\nWrite: $filename";
    file_put_contents( $filename, $contents );
}



######################
function get_tds( $tr )
{
    $arr = array();
    $end = 0;
    while(true)
    {
        $start  = strpos( $tr, '<td ', $end);
        if( $start == false ) break;
        $end    = strpos( $tr, '</td>', $start);
        if( $end == false ) break;
        $td = substr($tr, strpos($tr,'>',$start)+1, $end-$start);
        $td = html_entity_decode( $td );
        array_push($arr,$td);
    }
    return $arr;
}

######################
function get_link($td)
{
    $start  = strpos( $td, '<a ');
    if( $start === false ) return false;
    $end    = strpos( $td, '</a>', $start);
    if( $end === false ) return false;
    $a = substr($td, $start, $end-$start);
    
    $href_start = strpos($a,'href="') + 6;
    $href = substr($a, $href_start, strpos( $a, '" ', $href_start) - $href_start);
    $value = substr($a, strpos($a,'>',$href_start)+1, strpos($a, '</a>', $href_start));

    return $href;
}
?>
