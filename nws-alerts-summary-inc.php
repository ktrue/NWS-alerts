<?php 
###############################################################
#
#   NWS Public Alerts
#   Summary Page
#
#   This file is to be included in another page
#
###############################################################
// Version 1.01 - 03-Aug-2016 - updated for nws-alerts V1.37
// Version 1.02 - 27-Jan-2018 - updates for PHP7+ and nws-alerts V1.42
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include('nws-alerts-config.php');  // include the config/settings file

// overrides from Settings.php if available
global $SITE;
if(isset($SITE['cacheFileDir'])) {$cacheFileDir = $SITE['cacheFileDir'];}
if(isset ($SITE['tz']))          {$ourTZ = $SITE['tz'];}
if(!function_exists('date_default_timezone_set')) {
  putenv("TZ=" . $ourTZ);
} else {
  date_default_timezone_set("$ourTZ");
}
include($cacheFileDir.$cacheFileName);                                                        // include the data cache file

$alerts = '';                                                                                 // set variable
$fileUpdated = date("l g:ia T",filemtime($cacheFileDir.$cacheFileName));                       // get last modified time of the cache file
if(!empty($atomAlerts)) {                                                                     // IF there are alerts
  usort($atomAlerts, 'u_sort');                                                               //   sort locations
  foreach($atomAlerts as $aak => $aav) {                                                      //   FOR EACH alert, get the data from the cache file
    $alerts .= ' <tr>
  <td style="padding-left:6px"><b>'.$aav[0][12]."</b><br />\n";
    $caav = count($aav);                                                                      //     count alerts
    for($i=0;$i<$caav;$i++) {                                                                 //     FOR EACH alert
      (!empty($aav[$i][4])) ? $effective = date("D g:i a",$aav[$i][4]) : $effective = '';     //       get effective time
      (!empty($aav[$i][2])) ? $intensity = $aav[$i][2] : $intensity = ' - - -';               //       get intensity
      (!empty($aav[$i][5])) ? $expires = date("l n/j/Y g:i a",$aav[$i][5]) : $expires = '';         //       get expiration time
      if(!empty($intensity) and $intensity == 'Extreme') {
        $intensity = '<span style="color: red"><b> &nbsp;'.$intensity.'&nbsp; </b></span>';}  //       change intensity color
      if(!empty($intensity) and $intensity == 'Severe') {
        $intensity = '<span style="color: #F66"><b> &nbsp;'.$intensity.'&nbsp; </b></span>';} //        change intensity color
      if(!empty($intensity) and $intensity == 'Moderate') {
        $intensity = '<span style="-color: #FF9"><b> &nbsp;'.$intensity.'&nbsp;</b> </span>';}//        change intensity color
      // assemble alert data
      $alerts .= '   &nbsp;&nbsp;'.$aav[$i][10].'&nbsp;<a href="'.$alertURL.'?a='
                 .$aav[$i][14].'#WA'.$aav[$i][13].'" title=" &nbsp;Details for '.$aav[$i][12]
                 .' '.$aav[$i][0].'" style="color: #000;">'.$aav[$i][0].'</a> - Expires: '
                 .$expires."<br />\n";
    }
    $alerts .= '   <br />
  </td>
 </tr>
';
  }
}
// set no alert icon
$blankIcon = '<img src="'.$icons_folder.'/BNK.gif" width="12" height="12" alt=" No alerts" title=" No alerts" />';

if(!empty($noAlerts)) {                                                                       // IF there are no alerts for this location
  foreach($noAlerts as $nak => $nav) {                                                        //   FOR EACH no alert, assemble data
    $alerts .= ' <tr>
  <td style="padding-left:6px"><b>'.$nak."</b><br />\n";
    $alerts .= '   &nbsp;&nbsp;'.$blankIcon.'&nbsp;<a href="'.$alertURL
               .'?a='.$nav.'#WA1" title=" &nbsp;Details for '
               .$nak.'" style="color: #000;">No alerts</a>'."\n";
    $alerts .= '   <br /><br />
  </td>
 </tr>
';
  }
}


// FUNCTION - sort array
function u_sort($c, $d){
  if($c[0][11] == $d[0][11]){
    if($c[0][12] == $d[0][12]){ return 0; }
    elseif($c[0][12] > $d[0][12]){ return 1; }
    elseif($c[0][12] < $d[0][12]){ return -1; }
  }
  elseif($c[0][11] > $d[0][11]){ return 1; }
  elseif($c[0][11] < $d[0][11]){ return -1; }
} // end u-sort function

?>
<div style="width:632px; margin:0px auto 0px auto;">
 <table cellspacing="0" cellpadding="0" style="width:100%; margin:0px auto 0px auto; border:1px solid black; background-color:#F5F8FE">
  <tr>
   <td style="text-align: center; background: url(<?php 
     echo $icons_folder ?>/NOAAlogo1.png) no-repeat; background-position:center; padding:5px 0px 5px 0px"><h3>NWS Weather Alerts Summary</h3><p>Issued by the National Weather Service </p></td>
  </tr>
 </table>
 <p> </p>

 <table cellspacing="0" cellpadding="2" style="width:630px; margin: 0px auto 0px auto; border: 1px solid black; background-color: #EFEFEF">
  <tr>
   <td style="text-align: center; padding: 5px 10px 5px 10px; font-size:85%">Last update: <?php echo $fileUpdated ?><hr/></td>
  </tr>
 <?php echo $alerts ?>
 </table>
 <p> </p>
</div>
