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
// Version  1.06  14-May-2019 - replaced Google Map with Leaflet/OpenStreetMaps
// Version  1.07  15-May-2019 - fix validation errata

$Version = "nws-alerts-details-inc.php - V1.07 - 15-May-2019"; 

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include('nws-alerts-config.php');        // include the config/settings file

// overrides from Settings.php if available
global $SITE;
if(isset($SITE['cacheFileDir'])) {$cacheFileDir = $SITE['cacheFileDir'];}
if(isset($SITE['mapboxAPIkey'])) {$mapboxAPIkey = $SITE['mapboxAPIkey'];}
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
$sfllarea     = array();               // set variable

// get last modified time of the cache file
$fileUpdated  = date("D, n/j g:ia",filemtime($cacheFileDir.$cacheFileName));

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
  echo "<!-- The Map is not displaying -->\n";   //  display remark
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
			$sfllarea[] = $areas;
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
$leafletScript = '';
if(($displaymap == '1' and !empty($sfll))) {                                            // IF display map option is selected & polygon provided
  if(file_exists('nws-all-zones-inc.php') && !empty($zCode) && $displaymap !== '0') {       //  IF the shape file 
	  include_once('nws-all-zones-inc.php');  // load our Zones=>lat/lon centroid lookup table
    if(isset($loclatlon[$zCode])) {
			list($lat,$lon) = explode('|',$loclatlon[$zCode]);                                                 //   IF the zone code is in the shape file array
      $zcll = $lat.','.$lon;	                                                           //     get zone code latitude,longitude
      // set polygon overlays	
      if(!empty($sfll)) {                                                                      //  IF there are shape files
        $cp = count($sfll)*4+1;                                                                //   count shapes files, times by 4 & add - used for different line widths
        $polyLegend = ' <div style="width: 630px; text-align:center; background-color:#FFF">'; //   create the polygon legend
        $zcllA = preg_replace("/(.*)\,(.*)/", '[${1},${2}]', $zcll);                //   create center location formatted output from coordinates
        foreach($sfll as $zk => $zv) {                                                         //   FOR EACH shape file
          $cs = preg_replace("/(.*)\,(.*)/", '[${1},${2}]', $zv);                   //     create formatted polygon coordinates
          $cp = $cp/2;                                                                         //     divide number of shape files by 2
					if($cp < 1.0) {$cp = 1;}
					if($cp > 5.0) {$cp = 5;}
          $czc = count($zv);                                                                   //     format polygon coordinates
		      $zCoord .= over_layB($zk,$cp,$cs,$asf,$sfllarea);
          // create polygon legend
          $polyLegend .= '
  &nbsp; <span style="white-space: nowrap"><span style="color:black; font-size:75%; background-color:'.$rc
  .'; border:1px solid black;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
  <span style="color:black; font-size:75%;">'.$asf[$zk].' &nbsp;&nbsp;&nbsp;</span></span> &nbsp;';
        }
        $zCoord .= "\n// ]]>\n</script>\n";
        $polyLegend .= "\n </div>\n";
      }
      // put together google map javascript
			list($mapslist,$selectedmap) = make_map_selector ($mapProvider,$mapboxAPIkey);
      $gmjs .= '
<div id="map" style="width: 630px; height: 260px; border:1px solid black"></div>
<!-- Leaflet 1.0.3 javascript by Saratoga-weather.org -->
<script type="text/javascript">
// <![CDATA[
'.$mapslist.'
var map = L.map(\'map\', {
		center: new L.latLng('.$zcllA.'), 
		zoom: '.$zoomLevel.',
		layers: ['.$selectedmap.'],
		scrollWheelZoom: false
		});

  L.control.scale().addTo(map);
  L.control.layers(baseLayers).addTo(map);

';
    }
    $leafletScript = '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.3/leaflet.js" type="text/javascript"></script>'."\n";
  }
}

// FUNCTION - set google map polygon attributes
function over_layB($se,$pc,$po,$title,$affected) {
  global $rc, $atomAlerts;
  $cs = $se;
  $colors = array('#DF0101','#F79F81','#F2F5A9','#0174DF','#58FAD0',
                  '#FACC2E','#01DF01','#F7BE81','#FE2E64','#E0F8EC');
  $cc = count($colors);
  if(!isset($colors[$cs])) {$tr = range(0,$cs); shuffle($tr); $cs = $tr[0];}
  $rc = $colors[$cs];
  $ol =  "\n var polyc".$se." = [\n";
  foreach($po as $pk => $pv) {
    $ol	.=  "  ".$pv.",\n";
  }
	//$affected = isset($atomAlerts[$se][6])?'<br/>'.trim($atomAlerts[$se][6]):'';
  $ol .= ' ];
	
// $asf[$se]="'.$title[$se].'"
// $sfllarea[$se]="'.$affected[$se].'"
//
 var mapoly'.$se.' = new L.polygon(polyc'.$se.',{
  opacity: 1.0,
  color: "'.$rc.'",
  strokeOpacity: 0.9,
  weight: '.$pc.',
  fillColor: "'.$colors[$cs].'",
  fillOpacity: 0.20,
	title: "'.$title[$se].'"
 }).addTo(map);'."\n" . '

  mapoly'.$se.'.bindTooltip("'.$title[$se].'<br/>'.trim($affected[$se]).'", 
   { sticky: true,
     direction: "auto"
   });
';
  return $ol;
} // end of function

function make_map_selector ($mapProvider,$mapboxAPIkey) {

$output = '';
// table of available map tile providers
$mapTileProviders = array(
  'OSM' => array( 
	   'name' => 'Street',
	   'URL' =>'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		 'attrib' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
		 'maxzoom' => 18
		  ),
  'Wikimedia' => array(
	  'name' => 'Street2',
    'URL' =>'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png',
	  'attrib' =>  '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>',
	  'maxzoom' =>  18
    ),		
  'Esri_WorldTopoMap' =>  array(
	  'name' => 'Terrain',
    'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
	  'attrib' =>  'Tiles &copy; <a href="https://www.esri.com/en-us/home" title="Sources: Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community">Esri</a>',
	  'maxzoom' =>  18
    ),
	'Terrain' => array(
	   'name' => 'Terrain2',
		 'URL' =>'http://{s}.tile.stamen.com/terrain/{z}/{x}/{y}.jpg',
		 'attrib' => '<a href="https://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> <a href="https://stamen.com">Stamen.com</a> | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 14
		  ),
	'OpenTopo' => array(
	   'name' => 'Topo',
		 'URL' =>'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
		 'attrib' => ' &copy; <a href="https://opentopomap.org/">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>) | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 15
		  ),
	'MapboxTer' => array(
	   'name' => 'Terrain3',
		 'URL' =>'https://api.mapbox.com/styles/v1/mapbox/outdoors-v10/tiles/256/{z}/{x}/{y}?access_token='.
		 $mapboxAPIkey,
		 'attrib' => '&copy; <a href="https://mapbox.com">MapBox.com</a> | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 18
		  ),
	'MapboxSat' => array(
	   'name' => 'Satellite',
		 'URL' =>'https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v10/tiles/256/{z}/{x}/{y}?access_token='.
		 $mapboxAPIkey,
		 'attrib' => '&copy; <a href="https://mapbox.com">MapBox.com</a> | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 18
		  ),
			
	);
 if(isset($mapTileProviders[$mapProvider]) ) {
		$output .= "// using \$mapProvider = '$mapProvider' as default map tiles.\n";
	} else {
		$output .= "// invalid \$mapProvider = '$mapProvider' - using OSM for map tiles instead. -->\n";
		$mapProvider = 'OSM';
 }
 $mapTilesAttrib = ' | Script by <a href="https://saratoga-weather.org/scripts-quake.php#quakePHP">Saratoga-weather.org</a>';
	$mList = '';  
	$mFirstMap = '';
	$mSelMap = '';
 	$swxAttrib = ' | Map Script by <a href="https://saratoga-weather.org/">Saratoga-weather.org</a>';
	$mScheme = $_SERVER['SERVER_PORT']==443?'https':'http';
	foreach ($mapTileProviders as $n => $M ) {
		$name = $M['name'];
		$vname = 'M'.strtolower($name);
		if(empty($mFirstMap)) {$mFirstMap = $vname; }  // default map is first in list
		if(strpos($n,'Mapbox') !== false and 
		   strpos($mapboxAPIkey,'-API-key-') !== false) { 
			 $mList .= "\n".'// skipping Mapbox - '.$name.' since $mapboxAPIkey is not set'."\n\n"; 
			 continue;
		}
		if($mScheme == 'https' and parse_url($M['URL'],PHP_URL_SCHEME) == 'http') {
			$mList .= "\n".'// skipping '.$name.' due to http only map tile link while our page is https'."\n\n";
			continue;
		}
		if($mapProvider == $n) {$mSelMap = $vname;}
		$mList .= 'var '.$vname.' = L.tileLayer(\''.$M['URL'].'\', {
			maxZoom: '.$M['maxzoom'].',
			attribution: \''.$M['attrib'].$swxAttrib.'\'
			});
';
		$mOpts[$name] = $vname;
		
	}
	$output .= "// Map tile providers:\n";
  $output .= $mList;
	$output .= "// end of map tile providers\n\n";
	$output .= "var baseLayers = {\n";
  $mtemp = '';
	foreach ($mOpts as $n => $v) {
		$mtemp .= '  "'.$n.'": '.$v.",\n";
	}
	$mtemp = substr($mtemp,0,strlen($mtemp)-2)."\n";
	$output .= $mtemp;
	$output .= "};	\n";
	if(empty($mSelMap)) {$mSelMap = $mFirstMap;}
	// end Generate map tile options

	return(array($output,$mSelMap));
}
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
echo $leafletScript;  
echo $gmjs;
echo $zCoord;
echo $polyLegend; 
?>
 <p> </p>
<?php echo $alertDetails ?>
</div>
