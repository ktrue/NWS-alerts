<?php 
/*################################

 NWS Public Weather Alerts
 
 Settings for nws-alerts

*/################################
// Version 1.02  03-Aug-2016 - Add and modify switches for nws-alerts V1.37 base
// Version 1.03  27-Jan-2018 - fix default noCron
// Version 1.04  14-May-2019 - changes for Leaflet/OpenStreetMaps instead of Google map

$Version = "nws-alerts-config.php - V1.04 - 14-May-2019"; 

// *** denotes settings are overridden by the Settings.php file if using Saratoga Base-USA template set

// ZONE CODES & COUNTY CODES CAN BE FOUND AT https://alerts.weather.gov/
// LOCATION|Z CODE|C CODE
// Note: this array will be overridden by the Settings.php $SITE['NWSalertsCodes'] entry if using the Saratoga Base-USA template set

$myZC = array(
  "Santa Clara Valley|CAZ513|CAC085",
//  "Santa Cruz Mtns|CAZ512|CAC081|CAC085|CAC087",
  "Santa Cruz|CAZ529|CAC087",
//  "Monterey|CAZ530|CAC053",
//  "South/East Bay|CAZ508|CAC081",
//  "San Mateo Coast|CAZ509|CAC081",
//  "San Francisco|CAZ006|CAC075"
); 


## MAIN SETTINGS
// time zone
$ourTZ         = 'America/Los_Angeles';        // *** Time Zone     http://www.php.net/manual/en/timezones.america.php

// folders
$cacheFileDir  = './cache/';                // *** default cache file directory
$icons_folder  = './alert-images';          // folder that contains the icons. No slash on end

// file names
$cacheFileName = 'nws-alertsMainData.php';  // main data cache file name
$aboxFileName  = 'nws-alertsBoxData.php';   // alert box cache file name
$iconFileName  = 'nws-alertsIconData.php';  // big icons cache file
$alertURL      = 'wxnws-details.php';       // web page file name for complete details - Used with Saratoga Base USA template
$summaryURL    = 'wxadvisory.php';       // web page for the alert summary - Used with Saratoga Base USA template
//$alertURL      = 'nws-details.php';       // web page file name for complete details - Used for standard web pages
//$summaryURL    = 'nws-summary.php';       // web page for the alert summary - Used for standard web pages

## GENERAL SETTINGS
$noCron        = true;                     // true=not using cron, update data when cache file expires
//                                            false=use cron to update data
$updateTime    = 600;                       // IF $noCron=true - time span in seconds to retain cache file before updating
$floodType     = true;                      // true=add prefix 'Areal' or 'River' to Flood alert title   false=no prefix to Flood alert
$noAlertText   = 'No Warnings, Watches, or Advisories';  // Text to display for no alerts.

## ALERT LOGGING
$logAlerts     = false;         // true=log alerts    false=don't log alerts
$log_folder    = './alertlog'; // folder that contains the log files. No slash on end

## ALERT BOX SETTINGS
$useAlertBox   = true;         // true=use alert box & write data file   false= not using alert box & don't write file
$titleNewline  = true;         // true=new line for each title   false=string titles with other titles
$aBox_Width    = '99%';        // width of box  examples - $aBox_Width = '80%';  $aBox_Width = '850px';
$centerText    = true;         // true=center text in alert box    false=left align text
$showNone      = true;         // true=show 'NONE' if no alerts in alert box   false=don't show alert box if no alerts
$locSort       = 1;            // location name sort - use number listed below
//                                0 = sort location as listed in $myZC array
//                                1 = sort location alphabetically

$sortbyEvent   = 3;            // sort titles by severity in alert box & then by number listed below
//                                0 = location - duplicate events will be displayed
//                                1 = location - duplicate events removed
//                                2 = event - duplicate events will be displayed
//                                3 = event - duplicate events removed


## BIG ICONS
$iconLimit     = 0;            // the number of icons to display  0=show all
$addNone       = false;        // true=add NONE foreach location with no alerts        false= don't show any NONE
$shoNone       = true;         // true=show one 'NONE' if no alerts for all location   false=don't show one 'NONE' if no alerts for all location
$useIcons      = 3;            // select number below
//                                0 = don't use icons - the cache file will not be written
//                                1 = sort by alert - duplicate events will be displayed
//                                2 = sort by alert - duplicate events removed
//                                3 = single top alert icon for each location
//                                4 = sort by location - duplicate removed
//                                5 = sort by location - duplicate events will be displayed


## XML PAGE
$useXML   = false;                          // true=create XML RSS feed   false=not using RSS feed
$rssTitle = 'Area Weather Alerts';          // title for the RSS/XML page 


## Leaflet Map
$mapboxAPIkey = '--mapbox-API-key--';  // use this for the Access Token (API key) to MapBox
$zoomLevel  = '8';            // default zoom level
$displaymap = '1';            // map display on details page
//                               0 = do not display map
//                               1 = display map only when polygon coordinates are provided in alert
$mapProvider = 'Esri_WorldTopoMap'; // ESRI topo map - no key needed
//$mapProvider = 'OSM';     // OpenStreetMap - no key needed
//$mapProvider = 'Terrain'; // Terrain map by stamen.com - no key needed
//$mapProvider = 'OpenTopo'; // OpenTopoMap.com - no key needed
//$mapProvider = 'Wikimedia'; // Wikimedia map - no key needed
// 
//$mapProvider = 'MapboxSat';  // Maps by Mapbox.com - API KEY needed in $mapboxAPIkey 
//$mapProvider = 'MapboxTer';  // Maps by Mapbox.com - API KEY needed in $mapboxAPIkey 

###   END OF SETTINGS   ###

// self downloader code
if (isset($_REQUEST['sce']) && ( strtolower($_REQUEST['sce']) == 'view' or
   strtolower($_REQUEST['sce']) == 'show') ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   readfile($filenameReal);
   exit;
} 
// end nws-alerts-config.php