<?php
#download_all_overviews();
$all_dates = scrape_from_files();

#$json = html_entity_decode( json_encode($all_dates)); //returns utf8 encoded data or false
#file_put_contents('dates.json',$json);




######################
# create the icalendars from a array
# writes one file for ervery commitee
# and one file for all commitees   
function build_ical( $all_dates )
{
    function getIcalDate($time, $incl_time = true)
    {
        return $incl_time ? date('Ymd\THis', $time) : date('Ymd', $time);
    }


    foreach( $all_dates AS $commitee)
    {
            
            $out =  "\nBEGIN:VCALENDAR",
                    "\nVERSION:2.0",
                    "\nPRODID:http://www.example.com/calendarapplication/",
                    "\nMETHOD:PUBLISH",
                    "\nBEGIN:VEVENT",
                    "\nUID:461092315540@example.com",
                    '\nORGANIZER;CN="Rob Tranquillo, offenesdresden.de":MAILTO:rob.tranquillo@gmx.de',
                    "\nLOCATION:",
                    "\nSUMMARY:",
                    "\nDESCRIPTION:",
                    "\nCLASS:PUBLIC",
                    "\nDTSTART:20060910T220000Z",
                    "\nDTEND:20060919T215900Z",
                    "\nDTSTAMP:20060812T125900Z",
                    "\nEND:VEVENT",
                    "\nEND:VCALENDAR",
                    "\n\n";
    }

    
/*
BEGIN:VCALENDAR
VERSION:2.0
PRODID:http://www.example.com/calendarapplication/
METHOD:PUBLISH
BEGIN:VEVENT
UID:461092315540@example.com
ORGANIZER;CN="Rob Tranquillo, offenesdresden.de":MAILTO:rob.tranquillo@gmx.de
LOCATION:
SUMMARY:
DESCRIPTION:
CLASS:PUBLIC
DTSTART:20060910T220000Z
DTEND:20060919T215900Z
DTSTAMP:20060812T125900Z
END:VEVENT
END:VCALENDAR
 */
}

    
######################
# gets all session dates from the files in 
function scrape_from_files()
{
    $folder = './pages/';
    $files = scandir($folder);
    $files = array_slice($files, 2); //cut away . and .. 
    date_default_timezone_set('Europe/Berlin');
    $all_dates = array();

    foreach( $files AS $file)
    {
        // echo "\n -> $folder$file";
        $html = file_get_contents( $folder.$file );
        
        // $html = mb_convert_encoding($html, 'UTF-8');
        
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
            $td = substr($html, $start, $end-$start);

            //dates with links are shurly in the past an all next dates in th document also
            if( substr_count( $td, '<a ' )) break; 

            //clean line from SMCINFO-tag
            $dateStr = trim( substr( $td, 0, strpos($td,'<!')));
            $timeStr = substr( $td, strpos($td,'->')+2);
            $timeStr = explode('-', $timeStr );  $timeStr = trim($timeStr[0]);  //sometimes the date is served in from-to Format, so we only want the starttime 
        
            // convert to unix timestamp
            $date = explode('.', $dateStr);
            $time = explode(':', $timeStr);
            $uxts = mktime( $time[0],$time[1],0,$date[1],$date[0],$date[2] );
            if( date("d.m.Y H:i", $uxts) != $dateStr.' '.$timeStr) echo ' error while getting date! ';
            else array_push( $dates, $uxts );
        }
        
        array_push( $all_dates, array( 'committee'=> $committee, 'dates' => $dates ) );
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
    $str = substr( $str, $start, $end - $start );
    return trim( $str );
}
    
    
######################
# Main Step one
function download_all_overviews()
{
    $path = 'http://ratsinfo.dresden.de/gr0040.php';

    $html = file_get_contents($path);
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
        if( trim($committee_name) == '' || substr_count($committee_name,'<!--S') > 0) continue;

        $sessions_cal_start = strpos($tds[3],'href='); 
        if( $sessions_cal_start !== false)         
        {
            $sessions_cal_start = $sessions_cal_start + 6;
            $sessions_cal_end = strpos($tds[3],'"', $sessions_cal_start);
            $sessions_cal_link = substr($tds[3], $sessions_cal_start, $sessions_cal_end - $sessions_cal_start); 
            $sessions_cal_link = 'http://ratsinfo.dresden.de/' . html_entity_decode($sessions_cal_link);
            array_push($committee,array(html_entity_decode($committee_name),$sessions_cal_link));
        }
    }

    ## last step: download every single sessions page
    foreach( $committee as  $pair ) download_sessions( $pair );
    
}


######################
function download_sessions( $arr )
{
    $savepath = "./pages/";
    $handle = fopen( $arr[1] , "rb");
    $contents = stream_get_contents($handle);
    fclose($handle);
    
    $filename = $savepath . str_replace(' ','_',$arr[0]);
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