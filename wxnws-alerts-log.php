<?php
############################################################################
# A Project of TNET Services, Inc. and Saratoga-Weather.org (Canada/World-ML template set)
############################################################################
#
#   Project:    Sample Included Website Design
#   Module:     sample.php
#   Purpose:    Sample Page
#   Authors:    Kevin W. Reed <kreed@tnet.com>
#               TNET Services, Inc.
#
# 	Copyright:	(c) 1992-2007 Copyright TNET Services, Inc.
############################################################################
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA
############################################################################
#	This document uses Tab 4 Settings
############################################################################
// Version 1.00 - 03-Aug-2016 - initial release with nws-alerts V1.37
// Version 1.01 - 27-Jan-2017 - PHP7+ fixes
require_once("Settings.php");
require_once("common.php");
############################################################################
$TITLE = langtransstr($SITE['organ']) . langtransstr(' -  NWS Alert Log');
$showGizmo = true;  // set to false to exclude the gizmo
include("top.php");
############################################################################
?>
</head>
<body>
<?php
############################################################################
include("header.php");
############################################################################
include("menubar.php");
############################################################################
?>

<div id="main-copy">
<?php 
// include the configuration file
include('nws-alerts-config.php'); 

// set timezone
if (isset ($SITE['tz'])) {
  $ourTZ = $SITE['tz'];
}
date_default_timezone_set("$ourTZ");

// initialize variables and arrays
$tdd = '';
$log_data = '';
$log_option ='';
$revData =array();
$lastDate = '';
$data2 = array();
$li_flip = array();
$log_info = array();
$log_file = '';
$daily_log = array();
$logged_date = date("m/d/Y");

// locate the nws-alerts log files
$log_filesN = glob($log_folder."/NWSalertLog*.txt");
foreach($log_filesN as $nwsfiles) {                                                      // FOR EACH dated alert log file
  if($nwsfiles and preg_match("|NWSalertLog(\d{8}).txt|i", $nwsfiles)){                  //   IF a NWS alert log was found
    preg_match("|NWSalertLog(20\d{6}).txt|i", $nwsfiles, $log_date);                     //     get date from file name
    $l_date = preg_replace("|(\d{4})(\d{2})(\d{2})|", '${2}/${3}/${1}', $log_date[1]);   //     make date readable
    $log_info[] = array($log_date[0],$l_date,$log_date[1]);                              //     Array([0] => Array([0] => NWSalertLog20120917.txt [1] => 09/17/2012 [2] => 20120917) 
    $li_flip[$l_date] = $log_date[0];                                                    //     Array([09/16/2012] => NWSalertLog20120916.txt
	}
}  

// locate the AtomAlert log files
$log_filesA = glob($log_folder."/AtomAlertLog*.txt");
if(!empty($log_filesA)){
  foreach($log_filesA as $atomfiles) {                                                        // FOR EACH dated alert log file
    if($atomfiles and preg_match("|AtomAlertLog(20\d{6}).txt|i", $atomfiles)){                 //   IF a NWS alert log was found
      preg_match("|AtomAlertLog(\d{8}).txt|i", $atomfiles, $log_dateOLD2);                     //     get date of log file from file name
      $l_dateOLD = preg_replace("|(\d{4})(\d{2})(\d{2})|", '${2}/${3}/${1}', $log_dateOLD2[1]);//     make readable date
      $log_info[] = array($log_dateOLD2[0],$l_dateOLD,$log_dateOLD2[1]);                       //     Array([0] => Array ([0] => NWSalertLog20120917.txt [1] => 09/17/2012 [2] => 20120917) 
      $li_flip[$l_dateOLD] = $log_dateOLD2[0];                                                 //     Array([09/16/2012] => NWSalertLog20120916.txt
    }
  }
}

// sort file array
rsort($log_info); 

// create the menu selection
foreach($log_info as $lik => $liv) {	
  $log_option .= '      <option value = "'.$liv[2].'">'.$liv[1].'</option>';
}

// GET logfile from menu
if(isset($_GET['logfile'])) {                                            // IF a date was selected in the menu
  $f_log = htmlspecialchars(strip_tags($_GET['logfile']));               //   clean up data
  if(is_file ($log_folder.'/NWSalertLog'.$f_log.'.txt')){                //   IF requested date is in an NWSalertLog file name
    $log_file = "NWSalertLog".$f_log.".txt";                             //     create requested log file name
    $logged_date = array_search($log_file, $li_flip);                    //     get date from NWSalertLog file
    include($log_folder.'/'.$log_file);                                  //     include the file
  }
  else{
    if(is_file ($log_folder.'/AtomAlertLog'.$f_log.'.txt')){             //   IF requested date is in an AtomAlertLog file name
      $log_file = "AtomAlertLog".$f_log.".txt";                          //     create requested log file name
      $logged_date = array_search($log_file, $li_flip);                  //     get date from AtomAlertLog
      include($log_folder.'/'.$log_file);                                //     include the file
    }
  }
}
else {                                                                 // OR ELSE
  if(array_key_exists("0",$log_info)) {                                //  IF date wasn't requested & there is log information
    include($log_folder.'/'.$log_info[0][0]);                          //  include the file
    $logged_date = array_search($log_info[0][0], $li_flip);            //  set the log date
  }
}

if(!empty($log_info)) {
  $lastDate = array_pop($log_info);
  $lastDate = ' since '.$lastDate[1];
}

$is_u = strtotime($logged_date);        // convert date to UNIX tile stamp
$long_date = gmdate("l F j, Y", $is_u); // convert the date
  
// date types
$log_date = date("Ymd");  
$yr = date("Y");
$day = date("D");
$old_time = date("h:i");
$new_time = date("g:i");
$add_day = '';

// count the alerts
$alert_count = count($daily_log);
$alertArea = '';
$alertAreas = '';
$aac = 0;
// start processing each log file
foreach($daily_log as $log =>$data) {  // FOR EACH alert in the log file
  $dcount = count($data);              //   count the alerts
  // if the array is from an old log file, convert it so it's useable	
  if(!array_key_exists("6",$data)){    //   IF the array key 6 is not in the array
    $data2[0] = $data[2];              //     format array
    $data2[1] = $data[0];              //     format array
    $data2[2] = strtotime($data[1]);   //     format array
    $data2[3] = strtotime($data[3]);   //     format array
    (!array_key_exists("4",$data)) ? $data2[4] = '' : $data2[4] = $data[4];                                 //     format array
    (!array_key_exists("5",$data)) ? $data2[5] = $data[0] : $data2[5] = $data[0];                           //     format array
    (!array_key_exists("6",$data) and array_key_exists("5",$data)) ? $data2[6] = $data[5] : $data2[6] = ''; //     format array
    preg_match("/^\.\.\.([A-Z].*\w)\.\.\./Uis",$data2[6],$abrvd);                                           //     get the first line in the alert description
    (isset($abrvd[1])) ? $data2[6] = $abrvd[1] : $data2[6] = '';                                            //     set the brief description
    $data = $data2;                                                                                         //     copy data
  }
  (!empty($data[2])) ? $issued = date("M j g:i a",$data[2]) : $issued = '';   //   set issue date
  (!empty($data[3])) ? $expired = date("M j g:i a",$data[3]) : $expired = ''; //   set expire date
  (!preg_match('/gif/',$data[4])) ? $data[4] = '' : $data[4] = $data[4];      //   set icon file name
  (isset($data[6])) ? $data[6] = $data[6] : $data[6] = '';                    //   set brief description

  // create data array
  if(!empty($issued) and !empty($expired)) {
  $revData[$data[1]][$data[0]][] = array($data[0],$data[1],$issued,$expired,$data[4],$data[5],$data[6],$data[2],$data[3]);
  }
}

// IF there is data, create the table
if($revData){                           // IF there is data
  $tdd .= '<div class="ajaxDashboard">'."\n";
  foreach($revData as $rdk => $rdv) {   //   FOR EACH alert
    $alertMark = '<a name="AL'.$aac.'" id="AL'.$aac.'"></a>'."\n";
    $tdd .= $alertMark;
    $rcount = count($rdv);              //     count alerts
    $fixLoc = str_replace(' ', "&nbsp;", $rdk);
    $alertArea .= '<a href="#AL'.$aac.'" title=" Go to '.$rdk.'">'.$fixLoc.'&nbsp;&nbsp;'.$rcount.'</a> &nbsp;';
    ($rcount == 1) ? $calerts = '' : $calerts = '<span style="font-size:90%"> &nbsp; '.$rcount.' alerts</span>';
    $tdd .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid black; margin:0px auto 18px auto; background-color:#000;">'."\n";
    $tdd .= '<tr>'."\n";
    $tdd .= '<td class="datahead" colspan="3" style="border-bottom:1px solid black; padding:2px 0px 2px"><span style="letter-spacing:2px; font-size:115%"><b>'.$rdk.'</b></span>'.$calerts.'</td>'."\n";
    $tdd .= '</tr>'."\n";
	
    if(isset($rdv)) {                     // IF there is an alert update
      foreach($rdv as $rdk2 => $rdv2) {   //   FOR EACH alert update
        $r2count = count($rdv2);          //     count updates
        (empty($rdv2[0][4])) ? $rdv2[0][4] = '' : $rdv2[0][4] = '<img src="'.$icons_folder.'/'.$rdv2[0][4].'" width="12" height="12" alt="'.$rdv2[0][4].'" title=" '.$rdv2[0][4].'" style="vertical-align:-8%" /> &nbsp; ';
        ($r2count == "1") ? $revsd = $revsd = '' : $revsd = '<span style="font-size:90%"> ('.$r2count. ' updates)</span>';                            //    set revision
        $tdd .= '<tr>'."\n";
        $tdd .= '<td colspan="3">'."\n";
        $tdd .= '<table width="96%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid black; margin:10px auto 10px auto">'."\n";
        $tdd .= '<tr>'."\n";
        $tdd .= '<td class="datahead" colspan="3" style="background-image: url('.$icons_folder.'/lbg.png); color:black"><b>'.$rdv2[0][4].strtoupper($rdk2).'</b> &nbsp; '.$revsd.'</td>'."\n";
        $tdd .= '</tr>'."\n";
        $rdv22c = 0;

        if(isset($rdv2[0])) {                              // IF there is an alert update
          foreach($rdv2 as $rdk22 => $rdv22) {             //   FOR EACH update
            $r22count = count($rdv22);                     //     count update
            ($rdv22c&1) ? $tbc = '#F1F1F1' : $tbc = '#FFF';//     set table background color
            $issue_u = $rdv22[7];                          //     set issue date
            $expire_u = $rdv22[8];                         //     set expire date
            $dur = $expire_u - $issue_u;                   //     set duration time
            $durationH =  gmdate("G",$dur);                //     convert duration hour
            $durationM =  gmdate("i",$dur);                //     convert duration minute
            if(floor($dur/86400) == 0) { $add_day = ""; }  //     set duration time
            elseif(floor($dur/86400) == 1) { $add_day = floor($dur/86400). " day &nbsp;"; }
            elseif(floor($dur/86400) >= 1) { $add_day = floor($dur/86400). " days &nbsp;"; }
            if($durationH == 1) { $durationH = $durationH. ' hr &nbsp;'; }
            else { $durationH = $durationH. " hrs &nbsp;"; }
            if($durationM == 01) { $durationM = $durationM. " min &nbsp;"; }
            else { $durationM = $durationM. " mins &nbsp;"; }
					
            if(!array_key_exists("6",$rdv22)){     // IF there is no brief description, set variables
              $rdv22[0] = $rdv22[2];
              $rdv22[1] = $rdv22[0];
              $rdv22[2] = strtotime($rdv22[1]);
              $rdv22[3] = strtotime($rdv22[3]);
              (!array_key_exists("4",$rdv22)) ? $rdv22[4] = '#FC0' : $rdv22[4] = $rdv22[4];
              (!array_key_exists("6",$rdv22) and array_key_exists("5",$rdv22)) ? $rdv22[6] = $rdv22[5] : $rdv22[6] = 'xoxoxoxox';
              (!array_key_exists("5",$rdv22)) ? $rdv22[5] = $rdv22[0] : $rdv22[5] = $rdv22[0];
            }
            $rdv22c++;
            if(!empty($rdv22[6])){
              $tdd .= '<tr>'."\n";
              $tdd .= '<td colspan="3" style="border-top:thin dotted black; text-align:center; background-color:'.$tbc.'; padding:4px 0px 4px"><b>'.$rdv22[6].'</b></td>'."\n";
              $tdd .= '</tr>'."\n";
            }
            $tdd .= '<tr>'."\n";
            $tdd .= '<td colspan="3" style="text-align:center; font-size:90%; background-color:'.$tbc.'; padding:6px 0px 8px"><b>Areas affected:</b> '.$rdv22[5].'</td>'."\n";
            $tdd .= '</tr>'."\n";
            $tdd .= '<tr>'."\n";
            $tdd .= '<td style="width:33%; text-align:center; font-size:90%; background-color:'.$tbc.'; border-right:dotted thin #777; padding:0px 0px 4px"><b>Effective:</b> '.$rdv22[2].'</td>'."\n";
            $tdd .= '<td style="width:33%; text-align:center; font-size:90%; background-color:'.$tbc.'; border-right:dotted thin #777; padding:0px 0px 4px"><b>Expired:</b> '.$rdv22[3].'</td>'."\n";
            $tdd .= '<td style="text-align:center; font-size:90%; background-color:'.$tbc.'; padding:0px 0px 4px"><b>Duration:</b> '.$add_day." ".$durationH." ".$durationM.'</td>'."\n";
            $tdd .= '</tr>'."\n";
          }
        }
        $tdd .= '</table>'."\n";
        $tdd .= '</td>'."\n";
        $tdd .= '</tr>'."\n";
      }
    }
    $tdd .= '</table>'."\n\n";
  $aac++;
  }
  $tdd .= '</div>'."\n";
}
else{
  $tdd .= '<div class="ajaxDashboard">'."\n";
  $tdd .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid black; margin:0px auto 18px auto;">'."\n";
  $tdd .= '<tr>'."\n";
  $tdd .= '<td style="text-align:center;">There are no log files for this date</td>'."\n";
  $tdd .= '</tr>'."\n";
  $tdd .= '</table>'."\n";
  $tdd .= '</div>'."\n";
}
if(!empty($alertArea)){                           // IF there is data
  $alertAreas = '  <tr>
    <td colspan="3" style="font-size:85%"><b>LOCATIONS &amp; ALERT COUNT: </b>'.$alertArea.'</td>
  </tr>
';
}

?>

<!-- start alert archive -->  
<div style="width:640px; margin:0px auto 0px auto">  
 <table width="100%" style="margin:0px auto 0px auto">
  <tr>
    <td class="data2" colspan="3" style="text-align: center"><br />Local weather alerts previously issued by the National Weather Service<?php echo $lastDate ?>.</td>
  </tr>
  <tr>
    <td colspan="3" style="text-align: center"></td>
  </tr>
  <tr align="center">
    <td colspan="3" style="font-size: 14px; font-family: Arial, Helvetica, sans-serif">
      <script type="text/javascript">
      <!--
      function menu_goto( menuform ){
       selecteditem = menuform.logfile.selectedIndex ;
       logfile = menuform.logfile.options[ selecteditem ].value ;
       if (logfile.length != 0) {
        location.href = logfile ;
       }
      }
      //-->
      </script>
      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" accept-charset="UTF-8" method="get">
      <p><span style="font-size:90%">Select date</span><br /> <select name="logfile" onChange="this.form.submit()">
      <option value="<?php echo $logged_date; ?>"><?php echo $logged_date; ?></option>
      <option value="" disabled="disabled"> </option>
      <option value="" disabled="disabled"> - Archive - </option>
<?php print $log_option ?>
      </select></p>
      <div><noscript><pre><input name="submit" type="submit" value="Submit" /></pre></noscript></div>
      </form>	
    </td>
  </tr>
  <tr>
    <td colspan="3" style="text-align:center; border:1px solid #444; padding:8px 0px 6px; background-color: #FFF"><span style="letter-spacing:2px; font-size:115%"><b><?php  print $long_date;
    ?></b></span><br /><span style="font-size: 90%"><?php  print $alert_count; ?> total alerts issued on this day</span></td>
  </tr>
  <?php echo $alertAreas ?>
  <tr>
    <td colspan="3">&nbsp; </td>
  </tr>
</table>

<?php echo $tdd; ?>
  
<!-- end alert archive -->  
</div>
    
</div><!-- end main-copy -->

<?php
############################################################################
include("footer.php");
############################################################################
# End of Page
############################################################################
?>