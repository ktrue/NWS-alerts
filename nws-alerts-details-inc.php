<?php
###############################################################
#
#   NWS Public Alerts
#   Detail Page
#
#   This file is to be included in another page
#
###############################################################
// Version: 1.02  12-May-2014 - Removed cookie control. Added map options
// Version: 1.03  21-Dec-2015 - Add month and day to effective & expire times
// Version: 1.04  03-Aug-2016 - Add Google Maps API
// Version: 1.05  27-Jan-2018 - Fix for PHP 7.1

$Version = "nws-alerts-details-inc.php - V1.05 - 27-Jan-2018"; 

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include('nws-alerts-config.php');        // include the config/settings file

// overrides from Settings.php if available
global $SITE;
if(isset($SITE['cacheFileDir'])) {$cacheFileDir = $SITE['cacheFileDir'];}
if(isset ($SITE['googleAPI']))   {$googleAPI = $SITE['googleAPI'];}
if(isset ($SITE['tz']))          {$ourTZ = $SITE['tz'];}
if(!function_exists('date_default_timezone_set')) {
  putenv("TZ=" . $ourTZ);
} else {
  date_default_timezone_set("$ourTZ");
}
include($cacheFileDir.$cacheFileName);// include the data cache file

// IF config file has not been updated, do not display the map
if(!isset($displaymap)) {$displaymap = '0';}

$alertDetails = '';                    // set variable
$nothing      = '';                    // set variable
$loclatlon    = array();               // set variable
$sfll         = array();               // set variable
$zcll         = '';                    // set variable
$jsmap        = '';                    // set variable
$gmjs         = '';                    // set variable
$zCode        = '';                    // set variable
$zCoord       = '';                    // set variable
$czCode       = '';                    // set variable
$asf          = array();               // set variable
$polyLegend   = '';                    // set variable

// get last modified time of the cache file
$fileUpdated  = date("D, n/j g:ia",filemtime($cacheFileDir.$cacheFileName));

// set the map style
switch($mapStyle){
  case 1:
    $mStyle = 'ROADMAP';
    break;
  case 2:
    $mStyle = 'SATELLITE';
    break;
  case 3:
    $mStyle = 'HYBRID';
    break;
  case 4:
    $mStyle = 'TERRAIN';
    break;
  default:
    $mStyle = 'ROADMAP';
    break;
}

if(isset($_GET['a']) && !empty($_GET['a'])) {                                  // IF the location code is set
  $czCode = htmlspecialchars(strip_tags($_GET['a']));                          //   clean up location code
	(preg_match("/$czCode/", $validCodes)) ? $czCode = $czCode : $czCode = ''; //   set variable to location code
	(preg_match("/\w{2}Z\d/", $czCode)) ? $zCode = $czCode : $zCode = '';      //   check for zone code
}
if(!isset($_GET['a']) or $czCode == '') {                                      // IF the location code is set or no code found
  $thisLoc = 'an unknown location';                                            //   set location to unknown
  $czCode = '';                                                                //   clear variable
  $nothing = 'Location has not been identified';                               //   set alert remark
}
if(!empty($noAlerts) and in_array($czCode,$noAlerts)) {                        // IF there are no alerts for current location and code is in no alert array
  $thisLoc = array_search($czCode,$noAlerts);                                  //   set variable to location
  $czCode = '';                                                                //   clear variable
  $nothing = 'No severe weather expected for '.$thisLoc;                       //   set alert remark
}

// check if display map is requested
if(!preg_match('/0|1/', $displaymap)){                  // IF the map display type variable is not valid
  $displaymap = '0';                                    //  don't display the map
  echo '<!-- $displaymap is set incorrectly -->'."\n";  //  display remark
  echo "<!-- The Google Map is not displaying -->\n";   //  display remark
}
// check for Google API key
if(!isset($googleAPI) and $displaymap != '0'){                             // IF the tag is not set and display map is requested
  $displaymap = '0';                                                       //  don't display the map
  echo '<!-- Google Map API tag $googleAPI is not found -->'."\n";         //  display remark
  echo "<!-- The Google Map is not displaying -->\n";                      //  display remark
}
if(isset($googleAPI) and preg_match('/^\-/', $googleAPI) and $displaymap == '1'){  // IF the tag is set AND key number has not been updated AND map is requested
  $displaymap = '0';                                                               //  don't display the map
  echo "<!-- A valid Google Map API key number has not been entered -->\n";        //  display remark
  echo "<!-- Google Map API key number found: $googleAPI -->\n";                   //  display remark
  echo "<!-- The Google Map is not displaying -->\n";                              //  display remark
}


if($nothing <> '') {                                                           // IF there are no alerts, or missing location, create alert table
  $alertDetails = '
<div style="width: 630px; margin: 0px auto 6px auto; border: 1px solid #000; background-color:#EFEFEF">
 <table width="600" border="0" cellspacing="0" cellpadding="0" style="margin: 10px auto 14px auto">
  <tr>
   <td style="text-align:center; color:#000; padding:4px 0px 2px;">'.$nothing.'</td>
  </tr>
 </table>
</div>
<p> </p>
';
}
if(!empty($czCode)) {                                                                           // IF there are alerts for the location
  foreach($atomAlerts[$czCode] as $aak => $aav) {                                               //   FOR EACH alert
    $thisLoc = $aav[12];                                                                        //     get the location
    $aav[7] = htmlspecialchars($aav[7]);                                                        //     change html characters
    $aav[8] = htmlspecialchars($aav[8]);                                                        //     change html characters
    (!empty($aav[4])) ? $effective = date("D, n/j g:ia",$aav[4]) : $effective = '';            //     get effective time
    (!empty($aav[2])) ? $intensity = $aav[2] : $intensity = ' - - -';                           //     get intensity
    (!empty($aav[1])) ? $urgency = $aav[1] : $urgency = ' - - -';                               //     get urgency
    (!empty($aav[5])) ? $expires = date("D, n/j g:ia",$aav[5]) : $expires = '';                //     get expiration time
    (!empty($aav[3])) ? $certainty = $aav[3] : $certainty = ' - - -';                           //     get certainty
    (!empty($aav[6])) ? $areas = $aav[6] : $areas = '';                                         //     get areas affected
    (!empty($aav[7])) ? $instruction = "<br />\n<b>Information:</b>\n"
                                       .'<pre style="white-space:pre-wrap">'
                                       .$aav[7].'</pre>' : $instruction = '';                   //     get information/instructions
    (!empty($aav[8])) ? $details = "\n<b>Details:</b>\n".'<pre style="white-space:pre-wrap">'
                                   .$aav[8].'</pre>' : $details = '';                           //     get details
    (!empty($aav[15])) ? $sf = $aav[15] : $sf = '';                                             //     get shape area
    if(!empty($sf)){                                                                            //     IF there is a shape area
      $sfll[] = explode(' ', $sf);                                                              //       create array of coordinates for shape files
      $asf[] = preg_replace("/\s/", '&nbsp;', $aav[0]);                                         //       create array for alert shape file
    }
		
    if(!empty($intensity) and $intensity == 'Extreme') {
      $intensity = '<span style="color: red"><b> &nbsp;'.$intensity.'&nbsp; </b></span>';}      //     change intensity color
    if(!empty($intensity) and $intensity == 'Severe') {
      $intensity = '<span style="color: #F66"><b> &nbsp;'.$intensity.'&nbsp; </b></span>';}     //     change intensity color
    if(!empty($intensity) and $intensity == 'Moderate') {
      $intensity = '<span style="color: #B77"><b> &nbsp;'.$intensity.'&nbsp;</b> </span>';}     //     change intensity color
    if(!empty($areas)) {$areas = preg_replace('/;/',' -',$areas);}                              //     change semi-colon to a dash
    ($displaymap != '1') ? $goto = '<a name="WA'.$aav[13].'" id="WA'.$aav[13].'"></a>' :
                           $goto = '';                                                          //     remove page link if map is displayed

    // put together all details
    $alertDetails .= ' <div style="width:630px; margin:0px auto 0px auto; border:1px solid '
                     .$aav[9].'; background-color:#EFEFEF">'.
  $goto.'
  <table width="610px" border="0" cellspacing="0" cellpadding="0" style="margin:10px auto 14px auto">
   <tr>
    <td colspan="3" style="text-align:center; color:'.$aav[9].'; padding:4px 0px 2px; font-size:110%">'
    .$aav[10].'&nbsp;&nbsp;<b>'.strtoupper($aav[0]).'</b>&nbsp;&nbsp;'.$aav[10].'</td>
   </tr>
   <tr>
    <td colspan="3" style="text-align:center; letter-spacing:2px; padding:4px 8px 4px 8px; font-size:115%"><b>'
    .strtoupper($aav[12]).'</b></td>
   </tr>
   <tr>
    <td colspan="3" style="text-align: center; padding:4px 12px 18px 12px"><hr style="color:'.$aav[9].'"/><b>Areas Affected:</b><br /> '.$areas.'</td>
   </tr>
   <tr>
    <td style="padding-left:28px;"><b>Effective:</b> '.$effective.'</td>
    <td style="padding-left:28px;"><b>Updated:</b> '.$fileUpdated.'</td>
    <td style="padding-left:28px;"><b>Urgency:</b> '.$urgency.'</td>
   </tr>
   <tr>
    <td style="padding-left:28px;"><b>Expires:</b> '.$expires.'</td>
    <td style="padding-left:28px;"><b>Severity:</b> '.$intensity.'</td>
    <td style="padding-left:28px;"><b>Certainty:</b> '.$certainty.'</td>
   </tr>
   <tr>
    <td colspan="3" style="text-align: center; padding:4px 12px 0px 12px"><hr style="color:'.$aav[9].'"/>&nbsp;</td>
   </tr>
   <tr>
    <td colspan="3" style="background-color: #FEFEF6; padding:10px; border: 1px solid '.$aav[9].'">'.$details.$instruction.'</td>
   </tr>
  </table>
 </div>
 <p> </p>
';
  }
}
//echo "<!-- displaymap='$displaymap' sfll='".print_r($sfll,true)."' zCode='$zCode'-->\n";
// MAP
// create the map
if(($displaymap == '1' and !empty($sfll))) {                                            // IF display map option is selected & polygon provided
  if(file_exists('nws-shapefile.txt') && !empty($zCode) && $displaymap !== '0') {       //  IF the shape file exists and a valid code and show map
    // get data from shapefile for google map
    $nsf = trim(file_get_contents('nws-shapefile.txt'));                                //   get nws shape file
    $nsf = str_replace('  ||', "",  $nsf);                                              //   replace double pipes
    $nsf = str_replace('|  ', "",  $nsf);                                               //   replace pipe & double space
    $zll = preg_replace("/([A-Z]{2})\|(\d+|\d+ )\|.*(\|\d+\.\d+\|)/", '$1Z$2$3', $nsf); //   get zone, latitude, longitude
    $zll = explode("\n", $zll);                                                         //   explode each line
    foreach($zll as $lk) {                                                              //   FOR EACH zone code, lat, lon
      list($loc, $lat, $lon) = explode('|', $lk);                                       //     list variables
      $lon = trim($lon);                                                                //     trim spaces off of longitude
      $loclatlon[$loc] = $lat.','.$lon;                                                 //     create array
    }
    if(array_key_exists($zCode, $loclatlon)) {                                                 //   IF the zone code is in the shape file array
      $zcll = $loclatlon[$zCode];	                                                           //     get zone code latitude,longitude
      // set polygon overlays	
      if(!empty($sfll)) {                                                                      //  IF there are shape files
        $cp = count($sfll)*4+1;                                                                //   count shapes files, times by 4 & add - used for different line widths
        $polyLegend = ' <div style="width: 630px; text-align:center; background-color:#FFF">'; //   create the polygon legend
        $zcllA = preg_replace("/(.*)\,(.*)/", '{lat: ${1}, lng: ${2}}', $zcll);                //   create center location formatted output from coordinates
        foreach($sfll as $zk => $zv) {                                                         //   FOR EACH shape file
          $cs = preg_replace("/(.*)\,(.*)/", '{lat: ${1}, lng: ${2}}', $zv);                   //     create formatted polygon coordinates
          $cp = $cp/2;                                                                         //     divide number of shape files by 2
          $czc = count($zv);                                                                   //     format polygon coordinates
		  $zCoord .= over_layB($zk,$cp,$cs);
          // create polygon legend
          $polyLegend .= '
  &nbsp; <span style="white-space: nowrap"><span style="color:black; font-size:75%; background-color:'.$rc
  .'; border:1px solid black;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
  <span style="color:black; font-size:75%;">'.$asf[$zk].' &nbsp;&nbsp;&nbsp;</span></span> &nbsp;';
        }
        $zCoord .= "}\n</script>\n";
        $polyLegend .= "\n </div>\n";
      }
      // put together google map javascript
      $gmjs .= '
<div id="map" style="width: 630px; height: 160px; border:1px solid black"></div>
<!-- Google maps v3 javascript -->
<script type="text/javascript">
 function initMap() {
  var map = new google.maps.Map(document.getElementById(\'map\'), {
  zoom: '.$zoomLevel.',
  center: '.$zcllA.',
  mapTypeId: google.maps.MapTypeId.'.$mStyle.'
 });
';
    }
    $zCoord .= '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key='.$googleAPI.'&amp;callback=initMap"></script>'."\n";
  }
}

// FUNCTION - set google map polygon attributes
function over_layB($se,$pc,$po) {
  global $rc;
  $cs = $se;
  $colors = array('#DF0101','#F79F81','#F2F5A9','#0174DF','#58FAD0',
                  '#FACC2E','#01DF01','#F7BE81','#FE2E64','#E0F8EC');
  $cc = count($colors);
  if(!array_key_exists($cs,$colors)) {$cs = shuffle(range(0,$cs));}
  $rc = $colors[$cs];
  $ol =  "\n var polyc".$se." = [\n";
  foreach($po as $pk => $pv) {
    $ol	.=  "  ".$pv.",\n";
  }
  $ol .= ' ]
 var mapoly'.$se.' = new google.maps.Polygon({
  paths: polyc'.$se.',
  strokeColor: "'.$colors[$cs].'",
  strokeOpacity: 0.8,
  strokeWeight: '.$pc.',
  fillColor: "'.$colors[$cs].'",
  fillOpacity: 0.20
 });'."\n";
  $ol .= " mapoly".$se.".setMap(map);\n";
  return $ol;
} // end of function

?>
<div style="width:632px; margin:0px auto 0px auto;">
 <table cellspacing="0" cellpadding="0" style="width:100%; margin:0px auto 0px auto; border:1px solid black; background-color:#F5F8FE">
  <tr>
   <td style="text-align:center; background:url(<?php 
     echo $icons_folder ?>/NOAAlogo1.png) no-repeat; background-position:center; padding:5px 0px 5px 0px"><h3>Weather Alerts for <?php
     echo $thisLoc; ?></h3><p>Issued by the National Weather Service </p></td>
  </tr>
 </table>
 <p> </p>
<?php  
echo $gmjs;
echo $zCoord;
echo $polyLegend; 
?>
 <p> </p>
<?php echo $alertDetails ?>
</div>
