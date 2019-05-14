<?php 
//error_reporting(E_ALL);
###############################################################
#
#   NWS Public Alerts
#
#   This key file culls alert data
#   No user settings in this file
#
###############################################################
/*

Version 1.10 - 21-July-2012   Added 2nd chance to cull data if timed out.
Version 1.11 - 26-July-2012   Added ability to have repeated County codes.
Version 1.12 - 27-July-2012   Fixed doubled alerts for zone & county.
Version 1.13 - 29-July-2012   Fixed icon cache file writing if set to 0.
Version 1.14 - 05-Aug-2012    Fixed spacing in the alert box.
Version 1.15 - 05-Aug-2012    Changed wording for alert box when there are no alerts..
Version 1.16 - 15-Sept-2012   Fixed invalid code entry.
Version 1.17 - 13-Oct-2012    Add log alert option.
Version 1.18 - 28-Oct-2012    Fixed expired alerts. Added detail title in alert log.
Version 1.19 - 4-Nov-2012     Added error detection for testing. Adjust times if missing.
Version 1.20 - 1-Dec-2012     Fixed expired alerts
Version 1.21 - 5-May-2013     Add option: Flood type to alert title
Version 1.22 - 12-June-2013   Add flood type to logged alerts and alert box
Version 1.23 - 23-Jan-2014    Fix strict error
Version 1.24 - 11-May-2014    Fix incorrect URL errors.
Version 1.25 - 2-July-2014    Fix failed data cull.
Version 1.27 - 14-Nov-2014    Fix end tag for img
Version 1.28 - 26-Dec-2014    Added alert - Hurricane Local Statement
Version 1.29 - 14-Jan-2015    Added alert - 911 Telephone Outage Emergency
Version 1.30 - 28-Sept-2015   Added User agent and referrer to cURL
Version 1.31 - 23-Nov-2015    Removed header content type
Version 1.32 - 29-Nov-2015    Adjustments
Version 1.33 - 13-Dec-2015    Improve cURL function
Version 1.34 - 20-Dec-2015    Validate RSS output
Version 1.35 - 15-Jun-2016    Change in NWS primary URL
Version 1.36 - 18-Jun-2016    Turn off options if not in the config file
Version 1.37 - 03-Aug-2016    Add file get contents if cURL is not available
Version 1.38 - 03-Jan-2017    Fix PHP 7 errors
Version 1.39 - 25-Jan-2017    Fix sort locations as listed in $myZC array
Version 1.40 - 28-Feb-2017    Fix alert box when Special Weather Statement is issued
Version 1.41 - 07-Mar-2017    Adjust for PHP 7.1
Version 1.42 - 27-Jan-2018    Additional adjustments for PHP 7.1, use curl only, Saratoga USA template
Version 1.43 - 14-May-2019    Changed from Google to Leaflet/OpenStreetMap map displays

*/
$Version = "nws-alerts.php - V1.43 - 14-May-2019"; 

// self downloader code
if (isset($_REQUEST['sce']) && ( strtolower($_REQUEST['sce']) == 'view' or
    strtolower($_REQUEST['sce']) == 'show') ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain; charset=ISO-8859-1");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   readfile($filenameReal);
   exit;
} 

$noted = "<!-- $Version -->\n";  // display version remark
//ini_set('display_errors', 1); 
//error_reporting(E_ALL);

// check for adequate PHP version
if(phpversion() < 5) {
  echo 'Failure: This ATOM/CAP Advisory Script requires PHP version 5 or greater. You only have PHP version: ' . phpversion();
  exit;
}   
if(!function_exists('curl_init')) {
	echo "<pre>\n$Version.\n";
	echo "Error: nws-alerts.php requires curl to operate. curl is not functional on this PHP ".
	  phpversion()." installation.\n";
	$toCheck = array('simplexml_load_file',
	                 'curl_init','curl_setopt','curl_exec','curl_error','curl_close');

	print "\nStatus of needed built-in PHP functions:\n";

	foreach ($toCheck as $n => $chkName) {
		print "function $chkName ";
		if(function_exists($chkName)) {
			print " is available.\n";
		} else {
			print " is NOT AVAILABLE.\n";
		}
		
	}
	exit;
}
// start total timer
$time_startTotal = load_timer();

// include the configuration settings file
include('nws-alerts-config.php'); 
// set variables in case they are not in the config file
if(!isset($log_folder))    {$logAlerts = false;}
if(!isset($floodType))     {$floodType = false;}
if(!isset($filter_alerts)) {$filter_alerts = false;}

// overrides from Settings.php if available
if(is_file("Settings.php")) {include_once("Settings.php");}
global $SITE, $Status,$noted;
if(isset($SITE['cacheFileDir']))   {$cacheFileDir = $SITE['cacheFileDir'];}
if(isset($SITE['tz']))             {$ourTZ = $SITE['tz'];}
if(isset($SITE['NWSalertsCodes'])) {$myZC = $SITE['NWSalertsCodes'];}
if(isset($SITE['NWSalertsNoCron'])){$noCron = $SITE['NWSalertsNoCron']; }
if(isset($SITE['googleAPI']))      {$googleAPI = $SITE['googleAPI']; }

// set time zone
if(!function_exists('date_default_timezone_set')) {
  putenv("TZ=" . $ourTZ);
} else {
  date_default_timezone_set("$ourTZ");
}

$dataCache = $cacheFileDir.$cacheFileName;                    // path & file name for the cache data file
$aboxCache = $cacheFileDir.$aboxFileName;                     // path & file name for the alert box data file
$iconCache = $cacheFileDir.$iconFileName;                     // path & file name for the big icon data file

$priURL = 'https://alerts.weather.gov/cap/wwaatmget.php?x=';   // NWS URL

// initialize variables
$vCodes  = '';
$cmyzc   = 0;  // kt
$aBox    = '';
$box     = '';
$noData  = false;
$WA      = 1;  // kt
$logData = array();
$Status  = '';
$fltrd   = '^FF';
$ad      = array();
$noAlrt  = array();
$ai      = array();
$bi      = array();
$noA     = array();
$codeID  = array();
$ano     = array();
$norss   = array();
$abData  = array();
$rssData = array();
$adnoBI  = array();
$allzc   = array();
$allzc3  = array();
$atData  = array();
$sortbythis = array();
$sortMe  = array();
$uts = date("U");     // current unix time stamp
$timenow = date("D m-j-Y g:i a",$uts);
$seenIt  = array();
// display notice if more than 4 codes are checked without using a cron job
foreach ($myZC as $myv) {
	$tlist = explode('|',$myv.'|'); // split spec
	for ($i=1;$i<count($tlist);$i++) { //skip over description to only count Zones
		if(strlen($tlist[$i])>0) { $seenIt[$tlist[$i]] =1; }
	}
}
$cmyzc = count($seenIt);
$noted .= "<!-- $cmyzc unique Zone entries found. Zones='";
$noted .= join(',',array_keys($seenIt));
$noted .= "' -->\n";
if($cmyzc > 4 and $noCron == true) {
  echo "nws-alerts: Checking more than four warning/county codes can delay the loading of your pages. You should use a cron job to get the data.\n"; 
}   

// cache file update policy
$updateCache = true;  // update cache file(s)
if(file_exists($dataCache) and $noCron) {       // IF the data cache file exists and not using cron
  $cft = filemtime($dataCache);     //   get cache file last modified time
  $cfAge = $uts - $cft;             //   get cache file age in seconds
  if($cfAge < $updateTime) {        //   IF cache file has not expired
    $updateCache = false;           //     don't update
		$noted .= "<!-- cache age $cfAge seconds - no fetch needed -->\n";
  } else {
    $updateCache = true;           //     don't update
		$noted .= "<!-- cache age $cfAge seconds - fetch needed -->\n";
	}
}

if(!empty($_GET['mu'])) { //   IF you want to manually update the report   nws-alerts.php?mu=1
	$updateCache = true;    //     set update cache
	$noted .= "<!-- manual update of cache requested -->\n";  //     display notice
}

// display remark about cron
($noCron) ? $noted .= "<!-- Cron job not used -->\n" :
            $noted .= "<!-- Cron job enabled -->\n";

// check if config file is updated
if(!isset($noAlertText)) {
  $noAlertText = 'No alerts';
  $noted .= "<!-- nws-alerts-config.php needs to be updated -->\n";
}

###  IF UPDATE CACHE
if($updateCache) {
  if($filter_alerts and file_exists($faCacheFile)) {                             // IF filtered events and filter file exists
    $filter_alerts = true;                                                       //  turn on filtered events
    include($faCacheFile);                                                       //  include the filter file
    ($filtered_alerts <> '') ? $fltrd = $filtered_alerts : $fltrd = '^NF';       //  IF filtered events - set variables to No Filter
    $noted .= "<!-- Filtered events enabled -->\n";                              //  display remarks
  }
  // get preliminary links
  foreach ($myZC as $mv) {                                                       // FOR EACH zone/county code
    preg_match_all("/[A-Z]{3}\d{3}/i", $mv, $cds);                               //   grab the codes
    $locCode = $cds[0][0];                                                       //   get first code listed after location as reference
    $allzc[$locCode] = '';                                                       //   get first code listed after location as reference
    $vCodes .= $locCode.'|';                                                     //   list valid reference codes
    $loc = preg_replace("/\|.*$/", '', $mv);                                     //   grab the locatiion
    $ccds = count($cds[0]);                                                      //   count codes
    for ($i=0;$i<$ccds;$i++) {                                                   //   FOR EACH listed code
      if(!$zData = get_nwsalerts($priURL.$cds[0][$i].'&y=1')) {                              //     IF can't get  preliminary URL
        $noted .= "<!-- First attempt in getting preliminary URL failed -->\n";  //       create note
        sleep(1);                                                                //       wait a half second
        if($zData = get_nwsalerts($priURL.$cds[0][$i].'&y=1')) {                             //       IF URL cull was successful
          $noted .= "<!-- Second attempt successful -->\n\n";                    //         create note
       }
        else{                                                                    //       OR ELSE
          $noted .= "<!-- Second attempt failed & skipped -->\n\n";              //         create note
          $noData = true;                                                        //         set variable
        }
      }
      (isset($zData->entry)) ? $zSearch = $zData->entry : $zSearch = '';         //     set search varible
	  
      if(isset($zSearch[0])) {                                                   //     IF there is data available
        $noData = '';                                                            //      set variable
        $cccds=count($zSearch);                                                  //       count each alert
        for ($m=0;$m<$cccds;$m++) {                                              //       FOR EACH alert per code
          $za = trim($zSearch[$m]->id);                                          //         get the secondary URL link
          $ta = trim($zSearch[$m]->title);                                       //         get the secondary URL link
          if(!preg_match('/\?x=\w{12,}/Ui',$za) or preg_match("/$fltrd/",$ta)) { //         IF the link doesn't list an alert URL or the event is filtered
            $noA[$loc][] = $locCode;                                             //           assemble array for 'No alerts'
            if(preg_match("/$fltrd/",$ta)){                                      //           IF a filtered event is found
              $noted .= "\n<!-- Filtered out alert location: $loc -->\n";        //             display remark
              $noted .= "<!-- Filtered out event: $ta -->\n\n";                  //             display remark
            }
          }
          else {                                                                 //         OR ELSE
            $codeID[$za][$loc][] = $locCode;                                     //           assemble array of alert URL's & locations
          }
        }
      }
    }
  }

  // check for codes with 'No alerts' against similar location codes with alerts
  foreach ($codeID as $rk => $rv) {                                         // FOR EACH alert
    $noA = array_diff_ukey($noA, $rv, 'key_compare');                       //  remove location code from no Alerts is alert is found
  }

  ksort($noA);  // sort No alerts array by key
	
  // trim down No alert array
  foreach ($noA as $nk => $nv) {                                                                              // FOR EACH No alert
    $noAlrt[$nk] = $nv[0];                                                                                    //   create a new array
    $norss[$nk][] = array('150',$nk,$nv[0],'No Alerts',1
                          ,'Severe weather is not expected',$priURL.$nv[0].'&amp;y=0');                       //   create array for RSS/XML
  }
  
  $countCodeID = count($codeID);                                                                              // count locations with alerts
  // get main data
  foreach ($codeID as $ck => $cv) {                                                                           // FOR EACH primary alert
    if(isset($cv)) {                                                                                          //   IF there is an alert
      $clr = ''; $ico = ''; $sev = '';                                                                        //     set variables
      if(!$czR = get_nwsalerts($ck)){                                                                              //     IF can't download each alert
        $noted .= "<!-- First attempt in getting primary data failed -->\n";                                  //       create note
        sleep(1);                                                                                             //       wait a second
        if($czR = get_nwsalerts($ck)){                                                                             //       IF download alert was successful
          $noted .= "<!-- Second attempt successful -->\n\n";                                                 //         create note
        }
        else{                                                                                                 //       OR ELSE
          $noted .= "<!-- Second attempt failed & skipped -->\n\n";                                           //         create failure note
          $czR = '';                                                                                          //         set variable to null
          $noData = true;                                                                                     //         set variable
        }
      }
      (isset($czR->note)) ? $note = trim($czR->note) : $note = '';                                            //     get the note
      (isset($czR->info->event)) ? $event = trim($czR->info->event) : $event = '';                            //     get event
      (isset($czR->info->urgency)) ? $urgency = trim($czR->info->urgency) : $urgency = '';                    //     get urgency
      (isset($czR->info->severity)) ? $severity = trim($czR->info->severity) : $severity = '';                //     get severity
      (isset($czR->info->certainty)) ? $certainty = trim($czR->info->certainty) : $certainty = '';            //     get certainty
      (isset($czR->info->effective)) ? $effective = trim($czR->info->effective) : $effective = '';            //     get effective time
      (isset($czR->info->expires)) ? $expires = trim($czR->info->expires) : $expires = '';                    //     get expiration time
      (isset($czR->info->description)) ? $description = trim($czR->info->description) : $description = '';    //     get the full alert description
      (isset($czR->info->instruction)) ? $instruction = trim($czR->info->instruction) : $instruction = '';    //     get the full alert instruction
      (isset($czR->info->area->areaDesc)) ? $areaDesc = trim($czR->info->area->areaDesc) : $areaDesc = '';    //     get areas
      (isset($czR->info->area->polygon)) ? $poly = trim($czR->info->area->polygon) : $poly = '';              //     get poly areas
      (isset($czR->status)) ? $status = trim($czR->status) : $status = '';                                    //     get status
      (isset($czR->scope)) ? $scope = trim($czR->scope) : $scope = '';                                        //     get scope
      (isset($czR->msgType)) ? $msgType = trim($czR->msgType) : $msgType = '';                                //     get message type
      $event = ucwords($event);                                                                               //     set upper case for first word in event
      // check for flood type
      if($floodType and $czR <> '') {                                    // IF Flood type option is on and data was retrieved
        foreach($czR->info->parameter AS $element) {                     //  FOR EACH parameter
          if($element->valueName == 'VTEC' ) {                           //   IF there is VTEC
            $vtec2 = trim($element->value);                              //    trim the value
            if(preg_match("/\.XX\.\w\.\w/",$vtec2)) {                    //    IF FA is found in the value
              $event = 'Areal '. $event;                                 //     set variable to Areal
               if($filter_alerts == true and isset($dsrgrd['flaw'])) {   //     IF filter alerts and  filter Areal Flood alerts
                 $note = 'filtered';                                     //     set variable to expired
                 $cv = key($cv);                                         //     set varible to location name
                 $noted .= "<!-- Source above is a filtered event: $event -->\n\n";   //     display remark
              }
            }
            if(preg_match("/\.FL\.\w\.\w/",$vtec2)) {                    //    IF FL is found in the value
               $event = 'River '. $event;                                //     set variable to River
               if($filter_alerts == true and isset($dsrgrd['flrw'])) {   //     IF filter alerts and filter River Flood alerts
                 $note = 'filtered';                                     //     set variable to expired
                 $cv = key($cv);                                         //     set varible to location name
                 $noted .= "<!-- Source above is a filtered event: $event -->\n\n";   //     display remark
              }
            }
          }
        }
      }
      (isset($event)) ? $cis = get_icon($event) : $cis = '';                                                  //     get other variables for event
      (!empty($effective)) ? $effective = strtotime($effective) : $effective = '';                            //     convert time
      (!empty($expires)) ? $expires = strtotime($expires) : $expires = '';                                    //     convert time
      if(isset($cis)) {                                                                                       //     IF event varaibles
        $clr = $cis['color'];                                                                                 //       set event color
        $ico = $cis['icon'];                                                                                  //       set event icon
        $sev = $cis['severity'];                                                                              //       set event severity
      }
      (!empty($ico)) ? $ico = conv_icon($icons_folder,$ico,$event) : $ico = '';                               //     IF an icon name is found, convert name into icon
      if(!preg_match("/expired|filtered/",$note)) {                                                           //     IF alert hasn't expired or filtered out
        foreach($cv as $cvk => $cvv) {                                                                        //      FOR EACH listed code
          $cvv = array_unique($cvv);                                                                          //       remove duplicate values
          $lcount = count($cvv);                                                                              //       count location codes
          for($i=0;$i<$lcount;$i++) {                                                                         //       FOR EACH location code
            $lCode = $cvv[$i];                                                                                //         set variable to location code
            if($logAlerts) {                                                                                  //         IF logging alerts  $logAlerts = true;
              if(preg_match("/^\.\.\.([A-Z].*\w)\.\.\./Uis",$description)) {                                  //          IF there is a detail title
                preg_match("/^\.\.\.([A-Z].*\w)\.\.\./Uis",$description,$abbrvd);                             //           get the detail title
              }
              elseif(preg_match("/\n\.\.\.([A-Z].*\w)\.\.\.\n/Uis",$description)) {                           //          OR ELSE find a title in the description
                preg_match("/\n\.\.\.([A-Z].*\w)\.\.\.\n/Uis",$description,$abbrvd);                          //           get the title
              }
              else{                                                                                           //          OR ELSE
                preg_match("/^\.?([A-Z].*\w)\n/Uis",$description,$abbrvd);                                    //           get the first line in the description
              }
              (isset($abbrvd[1])) ? $abbrvDesc = $abbrvd[1] : $abbrvDesc = '';
              $logData[] = array($event,$cvk,$effective,$expires,$cis['icon'],$areaDesc,$abbrvDesc);          //         create array for logging alerts
            }
            $ad[$lCode][] = array($event,$urgency,$severity,$certainty,$effective,$expires,
                            $areaDesc,$instruction,$description,$clr,$ico,$sev,$cvk,$WA,$cvv[0],$poly,$ck);   //         create array with needed wx variables
          }
          $WA++;                                                                                              //       increment counter
        }
      }
      else {
        if(preg_match("/expired/",$description)) { $noted .= "<!-- Expired alert removed -->\n\n"; }          //   display remark
        if(preg_match("/filtered/",$description)) { $noted .= "<!-- $cv alert removed -->\n\n"; }             //   display remark
      }
    }
  }
	
  // sort active alerts by severity
  if(!empty($ad)) {                                                                          // IF alert data is not empty
    foreach ($ad as $adk => $adv) {                                                          //   FOR EACH location with alert data
      foreach ($adv as $advk => $advkv) {                                                    //     FOR EACH alert
        if(array_key_exists($advkv[14], $allzc) and $locSort == 0){                          //      IF the alert Zone code is in the array AND $locSort= 0 
          $allzc3[$advkv[14]] = '';                                                          //       create array using as listed/default location order
        }
        usort($adv, 'sev_sort');                                                             //     sort locations multiple alerts by severity
        $atData[$adk] = array();                                                                //     create sorted array
        $atData[$adk] = $adv;                                                                //     create sorted array
      }
    }
    if($locSort == 0){                                                                       //   IF sorting locations as listed in $myZC array
      $atData = array_replace_recursive($allzc3,array_intersect_key($atData, $allzc3));      //     create new sorted array
    }
  }
  
  // writing alert data to cache file
  if($noData == '') {
    $dcfo = fopen($dataCache , 'w');                                                         // data cache file open
    if(!$dcfo) {                                                                             // IF unable to open cache file for writing
      $noted .= "<!-- unable to open cache file -->\n";                                      //   display remark
    } 
    else {                                                                                   // OR ELSE
      $write = fputs($dcfo, "<?php \n \n".'$atomAlerts = '. var_export($atData, 1).";\n");   //   write all of the alert data
      $write = fputs($dcfo, "\n".'$noAlerts = '. var_export($noAlrt, 1).";\n");              //   write no alert data
      $write = fputs($dcfo, "\n".'$validCodes = '. var_export($vCodes, 1).";\n\n?>");        //   write valid codes
      fclose($dcfo);                                                                         //   close the cache file
      $noted .= "<!-- Cache file updated: $timenow -->\n";                                   //   display remark
    } 
  }
  else {
    $noted .= "<!-- NO cache files updated -->\n";                                           //   display remark
  }
  ($centerText) ? $ct = 'text-align:center;' : $ct = 'text-align:left;';                     //   set text alignment

  // alert box conditions for NO alerts
  if($useAlertBox and empty($atData)) {                                                      // IF using alert box and no alerts
    get_scc('150');                                                                          //   set alert box backgound color and text color
    $box .= "\n<!-- nws-alerts box -->\n"
         .'<div style="width:'.$aBox_Width
         .'; border:solid thin #006699; margin:0px auto 0px auto;">'."\n";
    if($showNone) {                                                                          //   IF showing "NONE', create alert box with No Alert
      $box .= ' <div style=" '.$bc.' '.$tc.' padding:4px 8px 4px 8px; text-align: center"><a href="'
           .$summaryURL.'" title=" &nbsp;View summary" style="text-decoration:none; '.$tc
           .'">'.$noAlertText.'</a></div>
</div>
';
    }
    else {                                                                                    //   OR ELSE, don't show alert box
      $box = '';
    }
  }

  // alert box conditions WITH alerts
  if($useAlertBox and !empty($atData)) {                                                       // IF use alert box & have data
    foreach ($atData as $aak => $aav) {                                                        //   FOR EACH location with data
      foreach ($aav as $avk => $avv) {                                                         //     FOR EACH alert data
        $abData[] .= "$avv[11]|$avv[12]|$avv[14]|$avv[0]|$avv[10]|$avv[9]|$avv[13]";           //       create data string
      }
    }
    // IF sort alphabetically
    if($locSort == 1) {                                                                        // IF sort alert box data
      natsort($abData);                                                                        //   perform a natural sort for array
    }
    // set alert box sorting conditions
    foreach ($abData as $aBk => $aBv) {
      // list = severity code, location name, location code, title, icon, color, alert sequence
      list($sc, $ln, $lc, $ttl, $icn, $clr, $as) = explode('|', $aBv . '|||');                         // create list for each alert
      $sortbythis[$sc][][] = array($sc, $ln, $lc, $ttl, $icn, $clr, $as);                              // option to get highest severity 
      if($sortbyEvent == 0)    : $sortMe[$ln][][] = array($sc, $ln, $lc, $ttl, $icn, $clr, $as);       // 0 sort by alert
      elseif($sortbyEvent == 1): $sortMe[$ln][$sc][] = array($sc, $ln, $lc, $ttl, $icn, $clr, $as);    // 1 sort by alert
      elseif($sortbyEvent == 2): $sortMe[$sc][][] = array($sc, $ln, $lc, $ttl, $icn, $clr, $as);       // 2 sort by alert
      elseif($sortbyEvent == 3): $sortMe[$sc][$ln][] = array($sc, $ln, $lc, $ttl, $icn, $clr, $as);    // 3 sort by location
      endif;
    }
	
    $aksba = array_keys($sortbythis);                                                          // get the highest severity keys
//    $aksba = array_keys($sortMe);                                                            // get the highest severity keys
    $abta = array_shift($aksba);                                                               // get first alert (key) severity code
    get_scc($abta);                                                                            // set alert box backgound color to most severe alert
    $setStyle = 'style="'.$tc.' text-decoration: none"';                                       // set text decoration
	
    // set alert box style
    $box .= "\n<!-- nws-alerts box -->\n"
         .'<div style="width:'.$aBox_Width
         .'; border:solid thin #000; margin:0px auto 0px auto;">'."\n"
         .' <div style=" '.$bc.' '.$ct.' '.$tc.' '.'padding:4px 8px 4px 8px">'."\n";

    // duplicate events will be displayed
    if($sortbyEvent == 0) {
      foreach ($sortMe as $sblk => $sblv) {
        $abt = strtoupper($sblk);                                                                //    capitalize event title
        $abt = str_replace(" ", "&nbsp;", $abt);                                                 //    replace space
        ($titleNewline) ? $spc = ' ' : $spc = '';                                                //    set spacing
        $box .= '  <span style="white-space: nowrap">&nbsp;<a href="'.$alertURL.'?a='
             .$sblv[0][0][2].'" '.$setStyle.' title=" &nbsp;View details"><b>'.$abt
             .'</b></a></span>&nbsp;&nbsp;-';                                                    //    icon & event title
        $csblv = count($sblv);                                                                   //    count each string
        for($i=0;$i<$csblv;$i++) {                                                               //    FOR EACH string of data
          $sblv[$i][0][3] = str_replace(" ", "&nbsp;", $sblv[$i][0][3]);                         //       replace spaces
          $box .= '<span style="white-space: nowrap">&nbsp;'.$sblv[$i][0][4]
               .'&nbsp;<a href="'.$alertURL.'?a='.$sblv[$i][0][2].'#WA'.$sblv[$i][0][6]
               .'" '.$setStyle.' title=" &nbsp;Details for '.$sblv[$i][0][1].' - '
               .$sblv[$i][0][3].'">'.$sblv[$i][0][3]
               .'</a>&nbsp;&nbsp;</span> ';                                                      //        link & details
          }
        ($titleNewline) ? $box .= "<br />\n" : $box .= "&nbsp;&nbsp;&nbsp; " ;                   //      set line break or spaces
      }
    }

    //  duplicate events removed
    if($sortbyEvent == 1) {
      ($titleNewline) ? $spc = ' ' : $spc = '';                                                  //    set spacing
      foreach ($sortMe as $sblk => $sblv) {                                                      //    FOR EACH location with data
        $box .= '  <span style="white-space: nowrap">&nbsp;<a href="'.$alertURL
             .'?a='.$sblv[key($sblv)][0][2].'" '.$setStyle
             .' title=" &nbsp;View details"><b>'.$sblk.'</b></a></span>&nbsp;&nbsp;-';           //       icon & event title
        foreach ($sblv as $sblvk => $sblvv) {                                                    //       FOR EACH string of data
          $abt = strtoupper($sblk);                                                              //         capitalize event title
          $abt = str_replace(" ", "&nbsp;", $abt);
          $sblvv[0][3] = str_replace(" ", "&nbsp;", $sblvv[0][3]);                               //         replace spaces
          $box .= '<span style="white-space: nowrap">&nbsp;'.$sblvv[0][4]
               .'&nbsp;<a href="'.$alertURL.'?a='.$sblvv[0][2].'#WA'.$sblvv[0][6].'" '.$setStyle
               .' title=" &nbsp;Details for '.$sblvv[0][1].' - '.$sblvv[0][3].'">'.$sblvv[0][3]
               .'</a>&nbsp;&nbsp;</span> ';                                                      //         link & details
        }
        ($titleNewline) ? $box .= "<br />\n" : $box .= "&nbsp;&nbsp;&nbsp; " ;                   //         set line break or spaces
    	}
    }

    // duplicate events will be displayed
    if($sortbyEvent == 2) {
      foreach ($sortMe as $sblk => $sblv) {                                                     //      FOR EACH location with data
        $abt = strtoupper($sblv[key($sblv)][0][3]);                                             //        capitalize event title
        $abt = str_replace(" ", "&nbsp;", $abt);                                                //         replace spaces
       ($titleNewline) ? $spc = ' ' : $spc = '';                                                //         set spacing
        $box .= '  <span style="white-space: nowrap">'.$sblv[key($sblv)][0][4]
             .'&nbsp;<a href="'.$summaryURL.'" '.$setStyle
             .' title=" &nbsp;View summary"><b>'.$abt.'</b></a></span>';                        //         icon & event title
        $csblv = count($sblv);
        for($i=0;$i<$csblv;$i++) {
          $sblv[$i][0][1] = str_replace(" ", "&nbsp;", $sblv[$i][0][1]);                        //         replace spaces
          $box .= '&nbsp;-&nbsp;<a href="'.$alertURL.'?a='.$sblv[$i][0][2].'#WA'
               .$sblv[$i][0][6].'" '.$setStyle.' title=" &nbsp;Details for '.$sblv[$i][0][1]
               .' - '.$sblv[$i][0][3].'">'.$sblv[$i][0][1].'</a> '.$spc;                        //         create link & location
        }
        ($titleNewline) ? $box .= "<br />\n" : $box .= "&nbsp;&nbsp;&nbsp; " ;                  //         set line break or spaces
      }
    }

    //  duplicate events removed
    if($sortbyEvent == 3) {
      foreach ($sortMe as $sblk => $sblv) {                                                     //      FOR EACH location with data
        $abt = strtoupper($sblv[key($sblv)][0][3]);                                             //         capitalize event title
        $abt = str_replace(" ", "&nbsp;", $abt);                                                //         replace spaces
        ($titleNewline) ? $spc = ' ' : $spc = '';                                               //         set spacing
        $box .= '  <span style="white-space: nowrap">'.$sblv[key($sblv)][0][4]
             .'&nbsp;<a href="'.$summaryURL.'" '.$setStyle
             .' title=" &nbsp;View summary"><b>'.$abt.'</b></a></span>';                        //         icon & event title
        foreach ($sblv as $sblvk => $sblvv) {
          $sblvv[0][1] = str_replace(" ", "&nbsp;", $sblvv[0][1]);                              //         replace spaces
          $box .= '&nbsp;-&nbsp;<a href="'.$alertURL.'?a='.$sblvv[0][2].'#WA'
               .$sblvv[0][6].'" '.$setStyle.' title=" &nbsp;Details for '.$sblvv[0][1]
               .' - '.$sblvv[0][3].'">'.$sblvv[0][1].'</a> '.$spc;                              //         create link & location
        }
        ($titleNewline) ? $box .= "<br />\n" : $box .= "&nbsp;&nbsp;&nbsp; " ;                  //         set line break or spaces
      }
    }
    $box .= " </div>
</div>
";
  }// END IF NOT EMPTY $atData
	
  // writing alert box data to file	
  if($useAlertBox and $noData == '') {                                                      // IF using alert box
    $abfo = fopen($aboxCache , 'w');                                                        //   alert box file open
    if(!$abfo) {                                                                            //   IF not alert box file open cache file for writing
      $noted .= "<!-- unable to open cache file -->\n";                                     //     display remark
    } 
    else {                                                                                  // OR ELSE
      $write = fputs($abfo, "<?php \n".'$alertBox = '. var_export($box, 1).";\n\n?>");      //   write all of the alert box data
      fclose($abfo);                                                                        //   close the cache file
      $noted .= "<!-- Alert box data file updated -->\n";                                   //   display remark
    } 
	}

  // construct big icons
  if(!empty($noA)) {                                                                        // IF there are 'No alerts'
    foreach($noA as $nok => $nov) {                                                         //   FOR EACH 'No alert'
      $ano[][] = array('150'.'|'.$nok.'|'.$alertURL.'|'.$nov[0]
                       .'|'.'1'.'|'.''.'|'.$icons_folder.'|');                              //     IF $addNone, create array with no alert data
    }
    $rssno = $ano;                                                                          //     create array for rss with no alert data
  }
	
  // arrays for sorting icons
  if($useIcons !== 0 and !empty($atData)) {
    foreach ($atData as $aak => $aav) {
    $bic = 0;              // set big icon count to zero
      $caav = count($aav); // count alerts
      for($i=0;$i<$caav;$i++) {
        // by alert - no duplicates
        if($useIcons == 2) { $bi[$aav[$i][11]][$aav[$i][12]] = array($aav[$i][11].'|'.$aav[$i][12].'|'.$alertURL
                             .'|'.$aav[$i][14].'|'.$aav[$i][13].'|'.$aav[$i][0].'|'.$icons_folder.'|');}
        // by alert - with duplicates
        if($useIcons == 1) { $bi[$aav[$i][11]][] = array($aav[$i][11].'|'.$aav[$i][12].'|'.$alertURL.'|'.$aav[$i][14]
                             .'|'.$aav[$i][13].'|'.$aav[$i][0].'|'.$icons_folder.'|');}
        // by location - no duplicates
        if($useIcons == 4) { $bi[$aav[$i][12]][$aav[$i][0]] = array($aav[$i][11].'|'.$aav[$i][12].'|'.$alertURL
                             .'|'.$aav[$i][14].'|'.$aav[$i][13].'|'.$aav[$i][0].'|'.$icons_folder.'|');}
        // by location - with duplicates
        if($useIcons == 5) { $bi[$aav[$i][12]][] = array($aav[$i][11].'|'.$aav[$i][12].'|'.$alertURL.'|'.$aav[$i][14]
                             .'|'.$aav[$i][13].'|'.$aav[$i][0].'|'.$icons_folder.'|');}
        $bic++; // increment counter for each alert per location
      }
      if($useIcons == 3) { $bi[$aav[0][11]][] = array($aav[0][11].'|'.$aav[0][12].'|'.$alertURL.'|'.$aav[0][14]
                           .'||'.$aav[0][0].'|'.$icons_folder.'|'.$bic);}
    }
    // top icons
    if($useIcons == 1 or $useIcons == 2) {  // IF #1 or #2 is selected
      ksort($bi);                           //   sort by key
    }
    else {                                  // OR ELSE 
      if($locSort == 1) {                   //   IF sort icons by location alphabetcally
        ksort($bi);                         //     sort by key
      }
    }
  }

  if($addNone) {	
    $bi = array_merge($bi,$ano);                                                                         //   merge alerts with no alerts
  }
  $k=1;
  foreach($bi as $tik => $tiv) {                                                                         // FOR EACH location with alerts (and no alerts)
    foreach($tiv as $tivk => $tivv) {                                                                    //   FOR EACH alert
      // list = sev code, loc name, alert URL, loc code, alert sequence, title, icon folder, alert count
      list($scode, $lname, $aurl, $lcode, $aseq, $titl, $icnf, $alrtc) = explode('|', $tivv[0] . '|||'); //     create a list
      $ai[$k] = create_bi($lname, $aurl, $lcode, $aseq, $titl, $icnf, $scode, $alrtc);                   //     create all icons array
      $k++;
    }
  }
  // construct icon output if NONE
  $bigIcos = $ai;
  if(empty($atData) and $shoNone and !$addNone) {                                                         // IF there are no big icons (no alerts)
      $bigIcos = array();	 // kt 1.42
      $bigIcos[1] = '<a href="'.$summaryURL
                    .'" title=" &nbsp;Summary" style="width:99%"><br /><img src="'
                    .$icons_folder
                    .'/A-none.png" alt="No alerts" width="74" height="18" /></a>';
	}

  // limit menu bar icons
  $icount = count($bigIcos);                                                               // count the big icons
  if($iconLimit !== 0 and $iconLimit < $icount) {                                          // IF within icon limit
    $bigIcos = array_slice($bigIcos, 0, $iconLimit);                                       //   remove the last x number of icons
    $idiff = $icount - $iconLimit;                                                         //   count the icons removed
    ($idiff == 1) ? $diff = "other" : $diff = "others";                                    //   set plural or singular text
    $otherIcos = array(" <br />\n".' <a href="'.$summaryURL
                .'" title=" View the summary" style="width:99%; text-decoration: none;">'
                ."+$idiff $diff</a>");                                                     //   assembled remaining icons text
    $bigIcos = array_merge($bigIcos,$otherIcos);                                           //   merge icons and remaining icons
  }

  if($noData == '' and $useIcons !==0) {                                                   // IF there is data and able to write cache file
    $bifo = fopen($iconCache , 'w');                                                       //  icon file open
    if(!$bifo) {                                                                           //  IF not alert box file open cache file for writing
      $noted .= "<!-- unable to open big icon cache file -->\n";                           //   display remark
    } 
    else {                                                                                 //  OR ELSE
      $write = fputs($bifo, "<?php \n".'$bigIcons = '. var_export($bigIcos, 1).";\n?>\n"); //   write all of the alert box data
      fclose($bifo);                                                                       //   close the cache file
      $noted .= "<!-- Icon data file updated -->\n";                                       //   display remark
    }
  }

  // create RSS Feed file	
  if($useXML and $noData == '') {                                                         // IF using RSS feed and there is data
    if(!empty($atData)) {                                                                 //   IF there is data
      foreach ($atData as $aak => $aav) {                                                 //     FOR EACH location with data
        foreach ($aav as $avk => $avv) {                                                  //       FOR EACH alert data, creat RSS data array
         $rssData[$avv[12]][] = array($avv[11],$avv[12],$avv[14],
                                       $avv[0],$avv[13],$avv[8],$avv[16]);
       }
     }
   }
  $ma = array_merge($rssData,$norss);                                                     //   merge alerts with no alerts
  // construct RSS/XML
  $dirn = dirname($_SERVER["PHP_SELF"]);
  ($dirn == '/') ? $dirn = '' : $dirn = $dirn;
  $xml_zone = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<!-- version 2.00 - 20-Dec-2015 -->
<channel>
  <title>'.$rssTitle.'</title>
  <link>http://'.$_SERVER["SERVER_NAME"].'</link>
  <atom:link href="https://alerts.weather.gov/cap/us.php?x=0/" rel="alternate" type="application/rss+xml" />
  <description>Courtesy of the National Weather Service</description>
  <language>en-us</language>
  <generator>RSS feed version 2.0 - 20-Dec-2015</generator>
  <copyright>Curly at ricksturf.com</copyright>
  <pubDate>'.date("D, d M Y H:i:s T").'</pubDate>
  <ttl>15</ttl>
  <lastBuildDate>'.date("D, d M Y H:i:s T").'</lastBuildDate>
'; 
  foreach($ma as $xdk => $xdv) {
    $xml_zone .= '  <item>
    <title>'.$xdk.'</title>
    <link>http://'.$_SERVER["SERVER_NAME"].$dirn.'/'.$alertURL.'?a='.$xdv[0][2].'#WA</link>
    <pubDate>'.date("D, d M Y H:i:s T").'</pubDate>
    <description>';
      $cxdv = count($xdv);
      for($i=0;$i<$cxdv;$i++) {
        $desc_length = strlen($xdv[$i][5]);
        if($desc_length <= 50) { $xdv[$i][5] = $xdv[$i][5];}
        else { 
          if($desc_length >= 221) {
            $pos = strpos($xdv[$i][5], ' ', 221);
            $xdv[$i][3] = strtoupper($xdv[$i][3]);
            $xdv[$i][5] = $xdv[$i][3].'&lt;br/&gt;'.str_replace(' & ', " and ", $xdv[$i][5]);
            $xdv[$i][5] = trim(substr($xdv[$i][5], 0, $pos)).' [more].... &lt;br/&gt;&lt;br/&gt;';
          }
          else {
            $xdv[$i][5] = $xdv[$i][5].' [more]... &lt;br/&gt;&lt;br/&gt;';
          }
        }
        $xml_zone .= $xdv[$i][5];
      }    
      $xml_zone .= ' &lt;br/&gt;</description>
';
      $xml_zone .= '  <guid>'.$xdv[0][6].'</guid>
  </item>
';
    }
    $xml_zone .= '</channel>
</rss>';
    $rssfo = fopen('nws-rssfeed.xml' , 'w');                               // feed file open
    if(!$rssfo) {                                                          // IF not alert box file open cache file for writing
      $noted .= "<!-- unable to open rss feed cache file -->\n";           //   display remark
    } 
    else {                                                                 // OR ELSE
      $write = fputs($rssfo, $xml_zone);                                   //   write all of the alert box data
      fclose($rssfo);                                                      //   close the cache file
      $noted .= "<!-- RSS feed file updated -->\n";                        //   display remark
    } 
  }
} // END IF UPDATE CACHE

// Logging alerts
if($logAlerts) {                                                           // IF logging alerts  $logAlerts = true;
  include("$dataCache");                                                   //  include the cache file
  $noted .= "<!-- Alert logging enabled -->\n";                            //  add note
  $log_date = date("Ymd");                                                 //  current date
  $log_file = $log_folder."/NWSalertLog".$log_date.".txt";                 //  get log file name
  $added_alert = '';                                                       //  set variable
  $dy_log = array();                                                       //  set variable
  $dly_log = array();                                                      //  set variable
  if($logData and !empty($logData[0][0])) {                                //  IF there is data
    foreach($logData as $lk => $lv) {                                      //   FOR EACH alert
      $dlog[] = array($lv[0],$lv[1],$lv[2],$lv[3],$lv[4],$lv[5],$lv[6]);   //    create data array
    }
    if(is_file($log_file)) {                                               //   IF the log file exists
      include($log_file);                                                  //    include the log file
      foreach($dlog as $dlk => $dlv) {                                     //    FOR EACH alert
        if(!in_array($dlv, $daily_log)) {                                  //     IF alert is not in the cache file
          $dy_log[] = $dlv;                                                //       create array with alert data
          $added_alert = 1;                                                //       enable file writing of data
        }
      }
      $dly_log = array_merge($daily_log, $dy_log);                         //    merge new data with cached data
    }
    else{                                                                  //   OR ELSE
      $dly_log = $logData;                                                 //    copy fresh data
      $added_alert = 1;                                                    //    enable file writing of data
    }
  }		
  if($added_alert) {                                                                   // IF write data is enabled
    $fplf = fopen($log_file,'wb');                                                     //  open the log file
    if($fplf) {                                                                        //  IF cache file opens
      fwrite($fplf, "<?php \n \n".'$daily_log = '. var_export($dly_log, 1).";\n\n?>"); //   write first line for php ant top warnings tag
      fclose($fplf);                                                                   //   close file
      $noted .= "<!-- Log file updated $timenow -->\n";                                //   display note
    }
    else{                                                                              //  OR ELSE if the cache file does not open
      $noted .= "<!-- ERROR writing log file data $timenow -->\n";                     //   display note
      $noted .= "<!-- Check file and folder permission levels  -->\n";                 //   display note
    }
  }
}

// FUNCTION - get color, severity, icon
function get_icon($evnt) {
  $a = array();
  $alert_types = array (
    array('N'=>'Tornado Warning',                 'C'=>'#A00', 'S'=>'0', 'I'=>'TOR.gif'),
    array('N'=>'Severe Thunderstorm Warning',     'C'=>'#B11', 'S'=>'1', 'I'=>'SVR.gif'),
    array('N'=>'Blizzard Warning',                'C'=>'#D00', 'S'=>'2', 'I'=>'WSW.gif'),
    array('N'=>'Hurricane Force Wind Warning',    'C'=>'#D00', 'S'=>'3', 'I'=>'HUW.gif'),
    array('N'=>'Heavy Snow Warning',              'C'=>'#D00', 'S'=>'4', 'I'=>'WSW.gif'),
    array('N'=>'Hurricane Warning',               'C'=>'#D00', 'S'=>'5', 'I'=>'HUW.gif'),
    array('N'=>'Hurricane Wind Warning',          'C'=>'#D00', 'S'=>'6', 'I'=>'HUW.gif'),
    array('N'=>'Tsunami Warning',                 'C'=>'#D00', 'S'=>'7', 'I'=>'SMW.gif'),
    array('N'=>'Tropical Storm Warning',          'C'=>'#D00', 'S'=>'8', 'I'=>'TRW.gif'),
    array('N'=>'Winter Storm Warning',            'C'=>'#D00', 'S'=>'9', 'I'=>'WSW.gif'),
    array('N'=>'Winter Weather Warning',          'C'=>'#D00', 'S'=>'10', 'I'=>'WSW.gif'),
    array('N'=>'Ashfall Warning',                 'C'=>'#D00', 'S'=>'11', 'I'=>'EWW.gif'),
    array('N'=>'Avalanche Warning',               'C'=>'#D00', 'S'=>'12', 'I'=>'WSW.gif'),
    array('N'=>'Civil Danger Warning',            'C'=>'#D00', 'S'=>'13', 'I'=>'WSW.gif'),
    array('N'=>'Coastal Flood Warning',           'C'=>'#D00', 'S'=>'14', 'I'=>'CFW.gif'),
    array('N'=>'Dust Storm Warning',              'C'=>'#D00', 'S'=>'15', 'I'=>'EWW.gif'),
    array('N'=>'Earthquake Warning',              'C'=>'#D00', 'S'=>'16', 'I'=>'WSW.gif'),
    array('N'=>'Extreme Cold Warning',            'C'=>'#D00', 'S'=>'17', 'I'=>'HZW.gif'),
    array('N'=>'Excessive Heat Warning',          'C'=>'#D00', 'S'=>'18', 'I'=>'EHW.gif'),
    array('N'=>'Extreme Wind Warning',            'C'=>'#D00', 'S'=>'19', 'I'=>'EWW.gif'),
    array('N'=>'Fire Warning',                    'C'=>'#D00', 'S'=>'20', 'I'=>'WSW.gif'),
    array('N'=>'Flash Flood Warning',             'C'=>'#D00', 'S'=>'21', 'I'=>'FFW.gif'),
    array('N'=>'Areal Flood Warning',             'C'=>'#D00', 'S'=>'22', 'I'=>'FFW.gif'),
    array('N'=>'River Flood Warning',             'C'=>'#D00', 'S'=>'23', 'I'=>'FLW.gif'),
    array('N'=>'Flood Warning',                   'C'=>'#D00', 'S'=>'24', 'I'=>'FFW.gif'),
    array('N'=>'Freeze Warning',                  'C'=>'#D00', 'S'=>'25', 'I'=>'FZW.gif'),
    array('N'=>'Gale Warning',                    'C'=>'#D00', 'S'=>'26', 'I'=>'HWW.gif'),
    array('N'=>'Hard Freeze Warning',             'C'=>'#D00', 'S'=>'27', 'I'=>'HZW.gif'),
    array('N'=>'Hazardous Materials Warning',     'C'=>'#D00', 'S'=>'28', 'I'=>'WSW.gif'),
    array('N'=>'Hazardous Seas Warning',          'C'=>'#D00', 'S'=>'29', 'I'=>'SMW.gif'),
    array('N'=>'High Surf Warning',               'C'=>'#D00', 'S'=>'20', 'I'=>'SMW.gif'),
    array('N'=>'High Wind Warning',               'C'=>'#D00', 'S'=>'31', 'I'=>'HWW.gif'),
    array('N'=>'Ice Storm Warning',               'C'=>'#D00', 'S'=>'32', 'I'=>'ISW.gif'),
    array('N'=>'Lake Effect Snow Warning',        'C'=>'#D00', 'S'=>'33', 'I'=>'SMW.gif'),
    array('N'=>'Lakeshore Flood Warning',         'C'=>'#D00', 'S'=>'34', 'I'=>'SMW.gif'),
    array('N'=>'Law Enforcement Warning',         'C'=>'#D00', 'S'=>'35', 'I'=>'WSA.gif'),
    array('N'=>'Nuclear Power Plant Warning',     'C'=>'#D00', 'S'=>'36', 'I'=>'WSW.gif'),
    array('N'=>'Radiological Hazard Warning',     'C'=>'#D00', 'S'=>'37', 'I'=>'WSW.gif'),
    array('N'=>'Red Flag Warning',                'C'=>'#D00', 'S'=>'38', 'I'=>'FWW.gif'),
    array('N'=>'River Flood Warning',             'C'=>'#D00', 'S'=>'39', 'I'=>'FLW.gif'),
    array('N'=>'Shelter In Place Warning',        'C'=>'#D00', 'S'=>'40', 'I'=>'WSW.gif'),
    array('N'=>'Sleet Warning',                   'C'=>'#D00', 'S'=>'41', 'I'=>'IPW.gif'),
    array('N'=>'Special Marine Warning',          'C'=>'#D00', 'S'=>'42', 'I'=>'SMW.gif'),
    array('N'=>'Typhoon Warning',                 'C'=>'#D00', 'S'=>'43', 'I'=>'WSW.gif'),
    array('N'=>'Volcano Warning',                 'C'=>'#D00', 'S'=>'44', 'I'=>'WSW.gif'),
    array('N'=>'Wind Chill Warning',              'C'=>'#D00', 'S'=>'45', 'I'=>'WCW.gif'),
    array('N'=>'Storm Warning',                   'C'=>'#D00', 'S'=>'46', 'I'=>'SVR.gif'),
    array('N'=>'Tropical Storm Wind Warning',     'C'=>'#D00', 'S'=>'47', 'I'=>'TRW.gif'),  #### ADDED  ver 1.24
    array('N'=>'Shelter In Place Warning',        'C'=>'#D00', 'S'=>'48', 'I'=>'CEM.gif'),  #### ADDED  ver 1.26

    array('N'=>'Air Stagnation Advisory',         'C'=>'#F60', 'S'=>'50', 'I'=>'SCY.gif'),
    array('N'=>'Ashfall Advisory',                'C'=>'#F60', 'S'=>'51', 'I'=>'WSW.gif'),
    array('N'=>'Blowing Dust Advisory',           'C'=>'#F60', 'S'=>'52', 'I'=>'HWW.gif'),
    array('N'=>'Blowing Snow Advisory',           'C'=>'#F60', 'S'=>'53', 'I'=>'WSA.gif'),
    array('N'=>'Coastal Flood Advisory',          'C'=>'#F60', 'S'=>'54', 'I'=>'FLS.gif'),
    array('N'=>'Small Craft Advisory',            'C'=>'#F60', 'S'=>'55', 'I'=>'SCY.gif'),
    array('N'=>'Dense Fog Advisory',              'C'=>'#F60', 'S'=>'56', 'I'=>'FGY.gif'),
    array('N'=>'Dense Smoke Advisory',            'C'=>'#F60', 'S'=>'57', 'I'=>'SMY.gif'),
    array('N'=>'Brisk Wind Advisory',             'C'=>'#F60', 'S'=>'58', 'I'=>'WIY.gif'),
    array('N'=>'Flash Flood Advisory',            'C'=>'#F60', 'S'=>'59', 'I'=>'FLS.gif'),
    array('N'=>'Flood Advisory',                  'C'=>'#F60', 'S'=>'60', 'I'=>'FLS.gif'),
    array('N'=>'Freezing Drizzle Advisory',       'C'=>'#F60', 'S'=>'61', 'I'=>'SWA.gif'),
    array('N'=>'Freezing Fog Advisory',           'C'=>'#F60', 'S'=>'62', 'I'=>'FZW.gif'),
    array('N'=>'Freezing Rain Advisory',          'C'=>'#F60', 'S'=>'63', 'I'=>'SWA.gif'),
    array('N'=>'Freezing Spray Advisory',         'C'=>'#F60', 'S'=>'64', 'I'=>'SWA.gif'),
    array('N'=>'Frost Advisory',                  'C'=>'#F60', 'S'=>'65', 'I'=>'FRY.gif'),
    array('N'=>'Heat Advisory',                   'C'=>'#F60', 'S'=>'66', 'I'=>'HTY.gif'),
    array('N'=>'Heavy Freezing Spray Warning',    'C'=>'#F60', 'S'=>'67', 'I'=>'SWA.gif'),
    array('N'=>'High Surf Advisory',              'C'=>'#F60', 'S'=>'68', 'I'=>'SUY.gif'),
    array('N'=>'Hydrologic Advisory',             'C'=>'#F60', 'S'=>'69', 'I'=>'FLS.gif'),
    array('N'=>'Lake Effect Snow Advisory',       'C'=>'#F60', 'S'=>'70', 'I'=>'WSA.gif'),
    array('N'=>'Lake Effect Snow and Blowing Snow Advisory', 'C'=>'#F60', 'S'=>'71', 'I'=>'WSA.gif'),
    array('N'=>'Lake Wind Advisory',              'C'=>'#F60', 'S'=>'72', 'I'=>'LWY.gif'),
    array('N'=>'Lakeshore Flood Advisory',        'C'=>'#F60', 'S'=>'73', 'I'=>'FLS.gif'),
    array('N'=>'Low Water Advisory',              'C'=>'#F60', 'S'=>'74', 'I'=>'FFA.gif'),
    array('N'=>'Sleet Advisory',                  'C'=>'#F60', 'S'=>'75', 'I'=>'SWA.gif'),
    array('N'=>'Snow Advisory',                   'C'=>'#F60', 'S'=>'76', 'I'=>'WSA.gif'),
    array('N'=>'Snow and Blowing Snow Advisory',  'C'=>'#F60', 'S'=>'77', 'I'=>'WSA.gif'),
    array('N'=>'Tsunami Advisory',                'C'=>'#F60', 'S'=>'78', 'I'=>'SWA.gif'),
    array('N'=>'Wind Advisory',                   'C'=>'#F60', 'S'=>'79', 'I'=>'WIY.gif'),
    array('N'=>'Wind Chill Advisory',             'C'=>'#F60', 'S'=>'80', 'I'=>'WCY.gif'),
    array('N'=>'Winter Weather Advisory',         'C'=>'#F60', 'S'=>'81', 'I'=>'WWY.gif'),

    array('N'=>'Tornado Watch',                   'C'=>'#F93', 'S'=>'90', 'I'=>'TOA.gif'),
    array('N'=>'Severe Thunderstorm Watch',       'C'=>'#F93', 'S'=>'91', 'I'=>'SVA.gif'),
    array('N'=>'High Wind Watch',                 'C'=>'#F93', 'S'=>'92', 'I'=>'WIY.gif'),
    array('N'=>'Hurricane Force Wind Watch',      'C'=>'#F93', 'S'=>'93', 'I'=>'HWW.gif'),
    array('N'=>'Hurricane Watch',                 'C'=>'#F93', 'S'=>'94', 'I'=>'HUA.gif'),
    array('N'=>'Hurricane Wind Watch',            'C'=>'#F93', 'S'=>'95', 'I'=>'HWW.gif'),
    array('N'=>'Typhoon Watch',                   'C'=>'#F93', 'S'=>'96', 'I'=>'HUA.gif'),
    array('N'=>'Avalanche Watch',                 'C'=>'#F93', 'S'=>'97', 'I'=>'WSA.gif'),
    array('N'=>'Blizzard Watch',                  'C'=>'#F93', 'S'=>'98', 'I'=>'WSA.gif'),
    array('N'=>'Coastal Flood Watch',             'C'=>'#F93', 'S'=>'99', 'I'=>'CFA.gif'),
    array('N'=>'Excessive Heat Watch',            'C'=>'#F93', 'S'=>'100', 'I'=>'EHA.gif'),
    array('N'=>'Extreme Cold Watch',              'C'=>'#F93', 'S'=>'101', 'I'=>'HZA.gif'),
    array('N'=>'Flash Flood Watch',               'C'=>'#F93', 'S'=>'102', 'I'=>'FFA.gif'),
    array('N'=>'Fire Weather Watch',              'C'=>'#F93', 'S'=>'103', 'I'=>'FWA.gif'),
    array('N'=>'Flood Watch',                     'C'=>'#F93', 'S'=>'105', 'I'=>'FFA.gif'),
    array('N'=>'Freeze Watch',                    'C'=>'#F93', 'S'=>'105', 'I'=>'FZA.gif'),
    array('N'=>'Gale Watch',                      'C'=>'#F93', 'S'=>'106', 'I'=>'GLA.gif'),
    array('N'=>'Hard Freeze Watch',               'C'=>'#F93', 'S'=>'107', 'I'=>'HZA.gif'),
    array('N'=>'Hazardous Seas Watch',            'C'=>'#F93', 'S'=>'108', 'I'=>'SUY.gif'),
    array('N'=>'Heavy Freezing Spray Watch',      'C'=>'#F93', 'S'=>'109', 'I'=>'SWA.gif'),
    array('N'=>'Lake Effect Snow Watch',          'C'=>'#F93', 'S'=>'110', 'I'=>'WSA.gif'),
    array('N'=>'Lakeshore Flood Watch',           'C'=>'#F93', 'S'=>'111', 'I'=>'FFA.gif'),
    array('N'=>'Tropical Storm Watch',            'C'=>'#F93', 'S'=>'112', 'I'=>'TRA.gif'),
    array('N'=>'Tropical Storm Wind Watch',       'C'=>'#F93', 'S'=>'113', 'I'=>'WIY.gif'),
    array('N'=>'Tsunami Watch',                   'C'=>'#F93', 'S'=>'114', 'I'=>'WSA.gif'),
    array('N'=>'Wind Chill Watch',                'C'=>'#F93', 'S'=>'115', 'I'=>'WCA.gif'),
    array('N'=>'Winter Storm Watch',              'C'=>'#F93', 'S'=>'116', 'I'=>'SRA.gif'),
    array('N'=>'Winter Weather Watch',            'C'=>'#F93', 'S'=>'117', 'I'=>'WSA.gif'),
    array('N'=>'Storm Watch',                     'C'=>'#F93', 'S'=>'118', 'I'=>'SRA.gif'),

    array('N'=>'Coastal Flood Statement',         'C'=>'#C70', 'S'=>'120', 'I'=>'FFS.gif'),
    array('N'=>'Flash Flood Statement',           'C'=>'#C70', 'S'=>'121', 'I'=>'FFS.gif'),
    array('N'=>'Rip Current Statement',           'C'=>'#C70', 'S'=>'122', 'I'=>'RVS.gif'),
    array('N'=>'Flood Statement',                 'C'=>'#C70', 'S'=>'123', 'I'=>'FFS.gif'),
    array('N'=>'Hurricane Statement',             'C'=>'#C70', 'S'=>'124', 'I'=>'HUA.gif'),
    array('N'=>'Hurricane Local Statement',       'C'=>'#C70', 'S'=>'124', 'I'=>'HUA.gif'),  #### ADDED
    array('N'=>'Lakeshore Flood Statement',       'C'=>'#C70', 'S'=>'125', 'I'=>'FFS.gif'),
    array('N'=>'Marine Weather Statement',        'C'=>'#C70', 'S'=>'126', 'I'=>'MWS.gif'),
    array('N'=>'Public Information Statement',    'C'=>'#C70', 'S'=>'127', 'I'=>'PNS.gif'),
    array('N'=>'River Flood Statement',           'C'=>'#C70', 'S'=>'128', 'I'=>'FLS.gif'),
    array('N'=>'River Statement',                 'C'=>'#C70', 'S'=>'129', 'I'=>'RVS.gif'),
    array('N'=>'Severe Weather Statement',        'C'=>'#F33', 'S'=>'130', 'I'=>'SVS.gif'),
    array('N'=>'Special Weather Statement',       'C'=>'#C70', 'S'=>'131', 'I'=>'SPS.gif'),
    array('N'=>'Beach Hazards Statement',         'C'=>'#C70', 'S'=>'132', 'I'=>'SPS.gif'),
    array('N'=>'Tropical Statement',              'C'=>'#C70', 'S'=>'133', 'I'=>'HLS.gif'),
    array('N'=>'Typhoon Statement',               'C'=>'#C70', 'S'=>'134', 'I'=>'TRA.gif'),

    array('N'=>'Air Quality Alert',               'C'=>'#06C', 'S'=>'140',  'I'=>'SPS.gif'),
    array('N'=>'Significant Weather Alert',       'C'=>'#F33', 'S'=>'141',  'I'=>'SWA.gif'),
    array('N'=>'Child Abduction Emergency',       'C'=>'#093', 'S'=>'142', 'I'=>'SPS.gif'),
    array('N'=>'Civil Emergency Message',         'C'=>'#093', 'S'=>'143',  'I'=>'SPS.gif'),
    array('N'=>'Local Area Emergency',            'C'=>'#093', 'S'=>'144',  'I'=>'SPS.gif'),
    array('N'=>'Hazardous Weather Outlook',       'C'=>'#093', 'S'=>'149',  'I'=>'SPS.gif'),
    array('N'=>'Hydrologic Outlook',              'C'=>'#093', 'S'=>'149',  'I'=>'SPS.gif'),
    array('N'=>'Extreme Fire Danger',             'C'=>'#D00', 'S'=>'82',   'I'=>'WSW.gif'),
    array('N'=>'Coastal Hazard',                  'C'=>'#C70', 'S'=>'135',  'I'=>'CFS.gif'),
    array('N'=>'Short Term',                      'C'=>'#093', 'S'=>'136',  'I'=>'NOW.gif'),
    array('N'=>'911 Telephone Outage',            'C'=>'#36C', 'S'=>'137',  'I'=>'SPS.gif'),
    array('N'=>'911 Telephone Outage Emergency',  'C'=>'#36C', 'S'=>'137',  'I'=>'SPS.gif'),
    array('N'=>'Evacuation Immediate',            'C'=>'EA00', 'S'=>'45',   'I'=>'SVW.gif'),
  );

  foreach ($alert_types as $a_type)  {
    if(strpos($evnt,$a_type['N']) !== false){
      $a['color']    = $a_type['C'];
      $a['severity'] = $a_type['S'];
      $a['icon']     = $a_type['I'];
      return $a;
    }
  }

   // if alert type is not in list
  if (strpos($evnt,"Warning") !== false) {
    $a['color']    = "#D11";
    $a['severity'] = 46;
    $a['icon']     = 'SVW.gif';
    return $a;
  }
  if (strpos($evnt,"Advisory") !== false) {
    $a['color']    = "#F60";
    $a['severity'] = 83;
    $a['icon']     = 'SWA.gif';
    return $a;
  }
  if (strpos($evnt,"Watch") !== false) {
    $a['color']    = "#F30";
    $a['severity'] = 119;
    $a['icon']     = 'SWA.gif';
    return $a;
  }
  if (strpos($evnt,"Statement") !== false) {
    $a['color']    = "#C70";
    $a['severity'] = 139;
    $a['icon']     = 'SWA.gif';
    return $a;
  }
  if (strpos($evnt,"Air") !== false) {
    $a['color']    = "#06C";
    $a['severity'] = 140;
    $a['icon']     = 'SPS.gif';
    return $a;
  }
  if (strpos($evnt,"Short") !== false) {
    $a['color']    = "#093";
    $a['severity'] = 136;
    $a['icon']     = 'NOW.gif';
    return $a;
  }
  if (strpos($evnt,"Emergency") !== false) {
    $a['color']    = "#093";
    $a['severity'] = 145;
    $a['icon']     = 'SPS.gif';
    return $a;
  }
  if (strpos($evnt,"Outage") !== false) {
    $a['color']    = "#36C";
    $a['severity'] = 146;
    $a['icon']     = 'SPS.gif';
    return $a;
  }
  if (strpos($evnt,"No alerts") !== false) {
    $a['color']    = "#333";
    $a['severity'] = 150;
    $a['icon']     = 'BNK.gif';
    return $a;
  }

  // if no matches yet, set default
  $a['color'] = "#333";
  $a['severity'] = 149;
  $a['icon'] = 'SPS.gif';
  return $a;
}


#####   FUNCTIONS

// FUNCTION - get data cURL
function get_nwsalerts($url)    {
  global $noted,$Version;                                                          // set global
  $data = '';
	$tStatus = '';                                                             // set variable
  $tn = date("g:i:s");                                                    // time now
  $ch = curl_init();                                                      // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $url);                                    // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                            // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 ('.$Version.')');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);                            // 3 sec connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, 2);                                   // 2 sec data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                         // return the data transfer
  $data = curl_exec($ch);                                                 // execute session
  if(curl_error($ch) <> '') {                                             // IF there is an error
   $noted .= "\n<!-- $tn -->\n";                                          //  display time notice
   $noted .= "<!-- Error: ". curl_error($ch) ." -->\n";                   //  display error notice
   $noted .= "<!-- Source: $url -->\n";
  }
  curl_setopt($ch, CURLOPT_NOBODY, false);                                // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                                 // get header information
  $header_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);                // set header information
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
  $tStatus .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'];
	if(isset($cinfo['primary_ip'])) {
    $tStatus .= " dest=".$cinfo['primary_ip'] ;
	}
	if(isset($cinfo['primary_port'])) { 
	  $tStatus .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $tStatus .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$tStatus .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $tStatus .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $tStatus .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";


  curl_close($ch);                                                        // close the cURL session
  
  if(preg_match("|text\/xml|",$header_type)) {                            // IF text/xml is found in the header
   $data = @simplexml_load_string($data);                                 //  xml string to object
   $noted .= "<!-- XML source: $url -->\n";
	 $noted .= $tStatus;
  }
  else {
    $data = '';
		$noted .= "<!-- non XML source: $url -->\n";
		$noted .= $tStatus;
		$noted .= "<!-- header type='$header_type' -->\n";
  }
  return $data;                                                           // return data
}// end get_nwsalerts


// FUNCTION - remove 'no alerts' if similar location code has alert
function key_compare($key1, $key2){
  if ($key1 == $key2) return 0;
  else if ($key1 > $key2) return 1;
  else return -1;
}// end remove 'no alerts'

// FUNCTION - sort array by severity
function sev_sort($a, $b){
  if($a[11] == $b[11]){
    if($a[12] == $b[12]){ return 0; }
    elseif($a[12] > $b[12]){ return 1; }
    elseif($a[12] < $b[12]){ return -1; }
  }
  elseif($a[11] > $b[11]){ return 1; }
  elseif($a[11] < $b[11]){ return -1; }
} // end u-sort function

// FUNCTION - sort icons by severity
function ico_sort($a, $b){
  if($a[2] == $b[2]){
    if($a[6] == $b[6]){ return 0; }
    elseif($a[6] > $b[6]){ return 1; }
    elseif($a[6] < $b[6]){ return -1; }
  }
  elseif($a[2] > $b[2]){ return 1; }
  elseif($a[2] < $b[2]){ return -1; }
} // end u-sort function

// FUNCTION - set background color (severity color code)
function get_scc($scc) {
  global $tc, $bc;                        // make colors global
  $tc = 'color: #000;';
  if($scc >= 0 and $scc <= 49) {          // warning background
    $bc = 'background-color:#CC0000;';
    $tc = 'color: white;';
  }
  if($scc >= 50 and $scc <= 89) {         // advisory background
    $bc = 'background-color:#FFCC00;';
  }
  if($scc >= 90 and $scc <= 119) {        // watch background
    $bc = 'background-color:#FF9900;';
  }
  if($scc >= 120 and $scc <= 149) {       // other background
    $bc = 'background-color:#E6E6E3;';
  }
  if($scc >= 150) {                       // none background
     $bc = 'background-color:#E6E6E3;';
  }
}// end background color function

// FUNCTION - convert icon name into icon
function conv_icon($if,$ic,$ti) {
  (!empty($ti)) ? $ti = 'alt="'.$ti.'" title=" '.$ti.'"' : $ti = 'alt=" " title=" "';
  $ico = ' <img src="'.$if.'/'.$ic.'" width="12" height="12" '.$ti.' />';
  return $ico;
}// end convert icon function

// FUNCTION - convert big icons
function create_bi($a,$b,$c,$d,$e,$g,$h,$i) {
  if($h >= 0 and $h <= 49) { $bi = "A-warn.png"; }
  if($h >= 50 and $h <= 89) { $bi = "A-advisory.png";	}
  if($h >= 90 and $h <= 119) { $bi = "A-watch.png"; }	
  if($h >= 120 and $h <= 139) { $bi = "A-statement.png"; }	
  if($h == 140) { $bi = "A-air.png"; }	
  if($h >= 141 and $h <= 149) { $bi = "A-alert.png"; }
  if($h == 150) { $bi = "A-none.png"; }
  if($i < 2) {$i = '';}
  if($i >= 2) {$i = $i - 1;}
  ($i == 1) ? $alrts = 'alert' : $alrts = 'alerts';
  if($i >= 1) {$i = '&nbsp; +'.$i.' additional '.$alrts;}
  $bico =  ' <a href="'.$b.'?a='.$c.'#WA'.$d.'" title=" &nbsp;Details for '.$a
           .'&nbsp;'.$e.$i.'" style="width:99%; text-decoration:none; padding-top:3px">'.$a.'<br /><img src="'.$g
           .'/'.$bi.'" alt=" &nbsp;Details for '.$a.'&nbsp;'.$e.$i
           .'" width="74" height="18" style="border:none; padding-bottom:3px" /><br /></a>
';
  return $bico;
}// end convert big icons function

function load_timer() { // mchallis added function
  list($usec, $sec) = explode(" ", microtime());
  return ((float) $usec + (float) $sec);
} // end function load_timeR

$time_stopTotal = load_timer();
$total_times = ($time_stopTotal - $time_startTotal);
$total_time = sprintf("%01.4f", round($time_stopTotal - $time_startTotal, 4));
// $total_time;
$noted .= "<!-- Total process time: $total_time seconds -->\n";                 //   display remark
echo $Status;
echo $noted;
// create status file
$notes = $noted;
$notes = preg_replace('/<!-- /','',$notes);
$notes = preg_replace('/ -->\n/',"\n",$notes);
$notes = "Script characteristics on last page load:\nLast update: ".date("D m-d-Y H:i T")."\n".$notes;

$notesfo = fopen($cacheFileDir.'nws-notes.txt', 'w');                           // open note file
$write = fputs($notesfo, $notes);                                               // write notes to file
fclose($notesfo);                                                               // close the file

// end nws-alerts.php 