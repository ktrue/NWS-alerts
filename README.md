# NWS-Alerts

## Script history

This script set was developed by Curly of ricksturf.com (Michiana Weather) -- he has retired from programming and has released the scripts to be maintained by Ken at Saratoga-weather.org.  These scripts are included in the Saratoga Base-USA weather website template set.
From Version 1.43, the Google map has been replaced by a Leaflet/OpenStreetMap map, and an API key is no longer required.  Support is provided for an
optional MapBox API key to enable additional base maps for display.

The following information is from Curly's original _NWS-alerts_read_me.html_ converted to Markdown for this README file.

## Description

This program is designed to get **all** alerts for one to several counties as supplied by the National Weather Service.

Public alerts are acquired from the National Weather Service in the Atom Syndication Format (ATOM).  
These feeds are updated about every two minutes by the NWS.

Acquiring all of the alerts for a given location can be rather difficult since the NWS splits up these alerts into two groups, or codes. Severe alerts are listed in a County code and less severe alerts are in a Zone code.  
Some large metropolitan areas may have several Zone codes and one County code which makes this a little more difficult to get all possible alerts for a single county.  

This nws-alerts program can get all of the alerts for a desired county using two to several codes.

Features:

*   No cron jobs are needed for one or two locations
*   Configurable alert box for the index page
*   Configurable big icons for the menu side bar
*   Optional RSS Feed page
*   Data is cached after a fresh download
*   A Leaflet map displays an outlined warning area

## Quick Links

- [Deciding to use a cron job](#T1)  
- [Installation & Setup](#T2)  
- [Settings](#T3)  
- [Location array set up - $myZC](#T8)  
- [Standard web page set up](#T4)  
- [Saratoga Base-USA set up](#T5)  
- [Troubleshooting](#T6)  


<a name="T1" id="T1"></a>

# Implementation - To cron or not to cron

Speed is the key factor on how to configure this program.

To get all of the alerts for a given location, at least one County code and one Zone code is needed. Some locations use more than one Zone code and some locations have more than one County code, especially in mountainous regions and large metropolitan areas like Baltimore. Each code entered for a location is separately checked for possible alerts and if an alert is found, or multiple alerts are found, a link to each alert is collected and then the alert is downloaded. Each code takes a certain amount of time to check and download. The more codes to check, the longer it takes the script to process. This is where the speed comes into play to determine if you need to run the nws-alerts program using a cron job or not.

<a name="T10" id="T10"></a>

The nws-alerts program is efficient getting these preliminary alert links. Under normal conditions it takes about one tenth of a second to search each code for an alert link. At times abnormal conditions exist on the internet such as traffic, NWS updating at time of call, NWS server readiness, etc. The nws-alerts program will time out after 3 seconds if the data is not available and then it moves on to the next code. If abnormal conditions persist, it could take up to six seconds the load the link for each location. This seldom occurs but is possible and it would slow down the script process time.

After the preliminary checks for alerts, the links are gathered and the nws-alerts program will then download the complete details for each alert. If you have two or more locations that have the same alert, the nws-alerts program will only download the details once eliminating redundant downloads to save time. Again, the speed is affected by the internet traffic and conditions at the NWS.  
MOST OF THE TIME it takes less than a second to download the preliminary links and primary details for two locations with alerts.

You can view the script load time by viewing the page source. It's listed as  Total script process time:

**Recommendation:**  
If you are going to use one or two locations using one Zone code and one County code for each location, then don't use the cron job and let the page visit invoke the script to get the data after the cache file expires.  
Getting fresh alert data after the cache file expires will slow down loading the index page about a second. This isn't much and not that noticeable.

Two or more locations should use a cron job, or Windows Task Scheduler, to get the data.  
This way the alert processing time will not slow down loading the index page because the alert information will always be available.

<a name="T2" id="T2"></a>

# Installation & Setup

Extract the nws-alerts.zip file.  
There are several files, such as web pages, a data file, a configuration file, and the main file.  
Icons are supplied in the folder named alert-images.  
Below is a description of the files and how to use them.

**nws-alerts.php** - The core file to "include" in your web page or to cron depending on the number of locations used. There is nothing to configure in this file. This file is uploaded to the web folder where your web pages are located.

**nws-alerts-config.php** - Configure file names, folders, alert box, icons, etc. This file is uploaded to the web folder where your web pages are located after you have made the necessary adjustments.

**nws-alerts-details-inc.php** - This file gathers data from the cache file and transforms it to a formatted output for the alert details. It gets 'included' into a web page.

**nws-details.php** - A standard details web page. It 'includes' the nws-alerts-details-inc.php file. You can also use this file as a reference on how to 'include' the nws-alerts-details-inc.php file in your own template theme.

**wxnws-details.php** - Details web page for the Saratoga Base-USA templates. It 'includes' the nws-alerts-details-inc.php file.

**nws-alerts-summary-inc.php** - This file gathers data from the cache file and transforms it to a formatted output for a summary of alerts page. It gets 'included' into a web page.

**nws-summary.php** - A standard web page to display a summary of the alerts. It 'includes' the nws-alerts-summary-inc.php file. You can also use this file as a reference on how to 'include' the nws-alerts-summary-inc.php file in your own template theme.

**wxadvisory.php** - Summary web page for the Saratoga Base-USA templates version 3 or higher. This file replaces your current file that you are using now. A switch in the Settings.php can change what gets displayed on this page. Either the nws-alerts summary data or the default atom-advisory data.

**nws-all-zones-inc.php** - This file is generated by data provided by the NWS. It contains all of the zone codes with their latitude and longitude. This file is used for getting the locations for the Leaflet map that is placed in the details page. (Note: the prior _nws-shapefile.txt_ file is no longer used after V1.43)

**alert-images folder** - All icons and images are in this folder. Upload the complete folder. You may already have this folder with the same icons but there are a few images added in this updated file.

**cache folder** - This folder contains three cache files and one status text file used for the initial set up.  
This cache folder along with the four files are uploaded to the web site.

<a name="T3" id="T3"></a>

## Settings

**nws-alerts-config.php**

The following default settings are for a **Saratoga Base-USA web site**.  
Non - Saratoga Base-USA users will user different file names as they are noted where needed.

<span style="color: #960;">$myZC  array()</span>  
The array where the county location names and Zone codes with County codes are entered.  
This is described below. [Reference](#T8)

<span style="color: #960;">$ourTZ = 'America/New_York';    // *** Time Zone http://www.php.net/manual/en/timezones.america.php</span>  
Time zone of your location. All dates and times reflect on this setting. The URL listed is where you can find the name of your time zone.  
This settings will be overridden by Settings.php entries if using the Saratoga Base-USA template set.

<span style="color: #960;">$cacheFileDir = './cache/';    // default cache file directory</span>  
Folder/directory where the cache files will be written and used.  
This settings will be overridden by Settings.php entries if using the Saratoga Base-USA template set.

<span style="color: #960;">$icons_folder = './alert-images';    // folder that contains the icons. No slash on end</span>  
Folder/directory that contain the icons and images used.

<span style="color: #960;">$cacheFileName = 'nws-alertsMainData.php';    // main data cache file name</span>  
Alert details are in this file and placed placed in cache folder specified at $cacheFileDir. This file will be included in the web page to display the details.

<span style="color: #960;">$aboxFileName = 'nws-alertsBoxData.php';    // alert box cache file name</span>  
Alert events and locations are in this file and placed placed in cache folder specified at $cacheFileDir. This file contains the mark up and data to display an alert box on the index page. It will be included in the index page where you want to display the alert box. Displaying the alert box is an option and explained below.

<span style="color: #960;">$iconFileName = 'nws-alertsIconData.php';    // big icons cache file</span>  
The file name used for the big icons used in the menu side bar. It contains the location and related alert icon along with the mark up. This cache file will need to be included in side bar code. See instructions below.

<span style="color: #960;">$alertURL = 'wxnws-details.php';    // web page file name for complete details</span>  
The file name of the web page to display the alert details for each location. The nws-alerts program uses this file name as a link when an icon or location is clicked in the alert box or the menu side bar. See instructions below.

NOTE: If you are not using the Saratoga Base-USA templates, enter this file name: <span style="color: #960;">nws-details.php</span>

<span style="color: #960;">$summaryURL = 'wxadvisory.php';    // web page for the alert summary</span>  
The file name of the web page to display a summary of each location and its alert status. The nws-alerts program uses this file name as a link when an event is clicked in the alert box. See instructions below.

NOTE: If you are not using the Saratoga Base-USA templates, enter this file name: <span style="color: #960;">nws-summary.php</span>

<span style="color: #960;">$noCron = true;    // true=not using cron, update data when cache file expires false=use cron to update data</span>  
If using one or two locations, set this to true. A cron job is not required and the cache files will update when the page is called after the cache file expires. Set this to false if using two or more locations and you will need to set up a cron job to update the cache files. This is explained above at Implementation - To cron or not to cron. Setting up a cron job is described below. [Reference - Standard](#T4)  [Reference - Saratoga Base-USA](#T5)

<span style="color: #960;">$updateTime = 600;    // IF $noCron=true - time span in seconds to retain cache file before updating</span>  
Used only if you are not using a cron job. This is the amount of time in seconds to retain the cache file in seconds. 600 equals 10 minutes. After the cache file reaches this time period, the cache file(s) expire and will update after the next page visit. This is not used if $noCron is set to false.

<span style="color: #960;">$floodType = true;    // true=add prefix 'Areal' or 'River' to Flood alert title false=no prefix to Flood alert</span>  
Some flood alert titles may be catagorized with Areal or River in the title. Example:  
set to true AREAL FLOOD WARNING  
set to false FLOOD WARNING

<span style="color: #960;">$noAlertText = 'No Warnings, Watches, or Advisories';    // Text to display for no alerts.</span>  
Text to display when there are no alerts for the location.

<span style="color: #960;">$logAlerts = true;    // true=log alerts    false=don't log alerts</span>  
Basic alert data can be saved and viewed for a given date. A sample alert is included in the nws-alerts package.

<span style="color: #960;">$log_folder = './alertlog';    // folder that contains the log files. No slash on end</span>  
Folder to save the alert logs for reference.

<span style="color: #960;">$useAlertBox = true ;    // true=use alert box & write data file    false= not using alert box & don't write file</span>  
If you are going to use the alert box, set this to true to write the cache file. If you are not going to use the alert box, set this to false and the nws-alerts program will not write this cache file.

<span style="color: #960;">$titleNewline = true ;    // true=new line for each title   false=string titles with other titles</span>  
If you are going to use the alert box, set this to true to create a new line for each event or location. Setting this to false will string the events/titles on the same line depending if the alert box is wide enough.

<span style="color: #960;">$aBox_Width = '99%';    // width of box   Examples - $aBox_Width = '100%';   $aBox_Width = '850px';</span>  
Width of the entire alert box where it is placed in the index page. Adjust this to fit the width to your likings.

<span style="color: #960;">$centerText = true ;    // true=center text in alert box false=left align text</span>  
Set to true if you want the text centered in the alert box. Setting this to false will left align the text.

<span style="color: #960;">$showNone = true ;    // true=show 'NONE' if no alerts in alert box   false=don't show alert box if no alerts</span>  
If there are no alerts for any location, set to true if you want the alert box to display with "No alerts' displayed. Setting this to false will not display the alert box if there are no alerts for any location.

<span style="color: #960;">$locSort = 1 ;    // 0 = sort location as listed in $myZC array   1 = sort location alphabetically</span>  
Location name display. Either as listed in the array or alphabetically.

<span style="color: #960;">$sortbyEvent = 3;   // sort titles in alert box by number listed below</span>  
0 = location - duplicate events will be displayed  
1 = location - duplicate events removed  
2 = event - duplicate events will be displayed  
3 = event - duplicate events removed  
Locations are always sorted by severity and then sorted by location.

<span style="color: #960;">$iconLimit = 0;    / the number of icons to display      0=show all</span>  
Limits the number of icons in the side bar. This will display the number of icons and if there are more alerts, a short message will state the remaining amount of icons not displayed such as"+3 others".

<span style="color: #960;">$addNone = true;    // true=add NONE foreach location with No Alert at bottom of the list  
                                   false= don't show any NONE</span>  
If there is at least one location that has an alert, setting this to true will display the location without an alert with a 'NONE' icon. Setting this to false will not display any NONE icons.

<span style="color: #960;">$shoNone = true;    // true=show one 'NONE' if no alerts for all location    
                                   false=don't show one 'NONE' if no alerts for all location</span>  
If all locations do not have alerts, setting this to true will display one 'NONE' icon in the menu side bar. If set to false, the menu side bar will be blank if all locations do not have an alert. This only is effective if <span style="color: #960;">$addNone</span> is set to false.

<span style="color: #960;">$useIcons = 3;   // select number below</span>  
0 = don't use icons - the cache file will not be written  
1 = sort by alert - duplicate events will be displayed  
2 = sort by alert - duplicate events removed  
3 = single top alert icon for each location  
4 = sort by location - duplicate removed  
5 = sort by location - duplicate events will be displayed

<span style="color: #960;">$useXML = false;    // true=create XML RSS feed   false=not using RSS feed</span>  
If you plan to use the RSS Feed, set this to true. Setting this to false will not write the XML file.

<span style="color: #960;">$rssTitle = 'Area Weather Alerts';    // title for the RSS/XML page</span>  
The title to use for the RSS Feed.

<span style="color: #960;">$mapboxAPIkey = '-replace-this-with-your-API-key-here-';    // your **OPTIONAL** Mapbox API key;</span>  
Enter your Mapbox API key to display the OPTIONAL Mapbox.com tiled maps. If you leave the code as is, the free maps will display.  
If this setting is in the file Settings.php, you do not need to change this as it will be overwritten.

<span style="color: #960;">$zoomLevel = '8';    // default zoom level</span>  
Default zoom level for the Leaflet map.

<span style="color: #960;">$displaymap = '1';    // map display on details page  
</span>   // '0' = do not display map  
   // '1' = display map only when polygon coordinates are provided in alert  
Polygon coordinates are not always supplied in the alert to outline the affected area geographically. They are generally included when a warning or a severe statement is issued.

<span style="color: #960;">$mapProvider = 'Esri_WorldTopoMap'; // ESRI topo map - no key needed  
</span>   //$mapProvider = 'OSM'; // OpenStreetMap - no key needed  
   //$mapProvider = 'Terrain'; // Terrain map by stamen.com - no key needed  
   //$mapProvider = 'OpenTopo'; // OpenTopoMap.com - no key needed  
   //$mapProvider = 'Wikimedia'; // Wikimedia map - no key needed  
   //$mapProvider = 'MapboxSat'; // Maps by Mapbox.com - API KEY needed in $mapboxAPIkey  
   //$mapProvider = 'MapboxTer'; // Maps by Mapbox.com - API KEY needed in $mapboxAPIkey  
Selectable default map type for the Leaflet map.  

# Setting up pages to display the data

Be sure to back-up the files that are edited !

Before uploading all of the files, open nws-alerts-config.php and configure the $myZC array to your locations and check/adjust all remaining settings.  
You will also need to add a few lines of code in your index page for the alert box and/or the big icons as described later on.

<a name="T12" id="T12"></a>Make sure the cache file exists that is entered in the config file at $cacheFileDir. If that cache file does not exist, create it. This cache folder MAY need to have the permission levels adjusted. You will notice this if you get an error message about not being able to open a file or if a file is missing.

After making the necessary adjustments, upload the pages and then manually execute the script to populate the cache files.  
This is done by entering the following in the browsers URL bar:  
_http://yourwebsite.com/_**nws-alerts.php?mu=1**  
You can then fine tune the settings in the config file for the alert box and menu side bar icons, if you wish to do so.  
NOTE: If you change a setting, it won't take affect until the script updates the cache files. This can be done if you manually update the cache files by entering   _http://yourwebsite.com/_**nws-alerts.php?mu=1**   in the URL bar of your browser.

<a name="T8" id="T8"></a>

## $myZC set up with Zone code & County code

$myZC is the array where the location and codes are entered with a pipe | separating each other.  
The county location name is entered first, followed by a pipe, Zone (or County) code, a pipe, Zone (or County) code, a pipe, and any other codes related to the location. Notice the double quotes encasing each location and the comma.  
You can get your codes from the NWS. [Codes for the ATOM/CAP feeds](https://alerts.weather.gov/)  

_**Most**_ counties use one Zone code and one County code.  
The following is an example of one county location with the appropriate Zone and County code:  

```php
$myZC = array("Elkhart Co|INZ005|INC039");  
```

This example shows how to get alerts for three counties.

```php
$myZC = array(  
"Elkhart Co|INZ005|INC039",  
"St Joe Co|INZ004|INC141",  
"Branch Co|MIZ080|MIC023"  
);
```  

Some locations such as large metropolitan areas and mountainous areas use multiple Zone codes and sometimes a county can be split into multiple County codes.  
For instance, Baltimore has two Zone codes and two County codes. To get all of the alerts for Baltimore, the codes are all combined and the four codes will be separately checked.

```php
$myZC = array("Baltimore|MDZ006|MDZ011|MDC005|MDC510");  
```

The following example is for Baltimore and two counties:

```php
$myZC = array(  
"Baltimore|MDZ006|MDZ011|MDC005|MDC510",  
"Anne Arundel|MDZ014|MDC003",  
"Prince Georges|MDZ013|MDC033"  
);  
```

NOTE: If a Zone code is the first code entered, the Leaflet map will display in the details page. If a County code is the first code entered, the Leaflet map will not display.

NOTE: The FIRST code entered after the county name can not be repeated or used in another location. Any code after the first code can be used again for a different county.

<a name="T4" id="T4"></a>

## Standard web page set up

**$noCron - true or false ?**  
Somehow the data cache files need to be updated and that is determined by the setting $noCron in the  
nws-alerts-config.php file.

If you have a single location, you may want to set $noCron to true. When you choose 'true' for this option, the web page visit will update the cache files after the cache files have expired. The main file, nws-alerts.php, has to be included in a web page.

**IF   $noCron is set to true**  
Open your index file.  
Copy and paste the following line right after  <body>  (or similar):

```php
<?php include("nws-alerts.php"); ?>
```

Save the file.

**IF   $noCron is set to false**  
Do not perform the steps described above.

_How will the cache files update?_  
You will need to set up a cron job, use the Windows Task Scheduler, or a method that you prefer, to call the **nws-alerts.php** file at specified intervals. Since each web host has there own way of setting up a cron job, you will need to find out how to do this by getting the directions from your web host.  
Using the Windows Task Scheduler has been amazingly successful in doing this.  
Sample are available here: [For WinXP](./SetUp_XPcron.zip)    [For WinVISTA & Windows 7](./SetUp_VistaCron.zip)    [For WIN10](./WIN10_Task_Scheduler.zip)  

TIP: The NWS usually updates the feeds "about every two minutes". MOST of the time it's done on an even minute so I suggest running a cron on an odd minute to avoid long downloads or timing out on the data cull.  
If you use the Windows Task Scheduler, keep the PC time sync 'ed with the internet time.

**Adding the Alert Box**  
This will place the alert box on the index page only.  
Open the php index file.  
Add the following lines where you want the alert box to display:

```php
<?php  
// Add nws-alerts alert box cache file  
include_once("nws-alerts-config.php");  
include($cacheFileDir.$aboxFileName);  
// Insert nws-alerts alert box  
echo $alertBox;  
?>
```

Save the file, close it, and then upload it.

**Adding big icons**

The big icons can be added anywhere on a web page.  
A separate cache file stores these icons and they can be displayed any way you want which depends on your html and php expertise.

The amount of icons depends on how many locations you have plus the setting made in the  
nws-alerts-config.php file under  // BIG ICONS .  
Each icon is numbered and starts with the number one but again the amount of icons can vary.

To get all of the icons, some php code is needed and some html mark up may be added for the desired output. Below are two examples.

A simple way is to place the icons centered in a division like this:

```php
<?php  
include_once("nws-alerts-config.php"); // include the config file  
include($cacheFileDir.$iconFileName); // include the big icon file  
// construct icons  
$bigIcos = '<div style="text-align:center">'."\n";  
foreach($bigIcons as $bigI) {  
$bigIcos .= $bigI;  
}  
$bigIcos .= " <br />\n</div>\n<!-- end nws-alerts icons -->\n";  
echo $bigIcos; ?>  
```

Putting the icons in a table:

```php
<?php  
include_once("nws-alerts-config.php"); // include the config file  
include($cacheFileDir.$iconFileName); // include the big icon file  
// construct icons  
$bigIcos = '';  
$biCount = count($bigIcos);  
foreach($bigIcons as $bigI) {  
$bigIcos .= '<td colspan="'.$biCount.'">'.$bigI."</td>\n";  
}  
?>  
<table border="0" style="text-align:center">  
<tr>  
<?php echo $bigIcos; ?>  
</tr>  
</table>  
```

OR

```php
<?php  
include_once("nws-alerts-config.php"); // include the config file  
include($cacheFileDir.$iconFileName); // include the big icon file  
// construct icons  
$bigIcos = '';  
foreach($bigIcons as $bigI) {  
$bigIcos .= "<tr>\n<td style=\"text-align:center\">".$bigI."</td>\n</tr>\n";  
}  
?>  
<table border="0">  
<?php echo $bigIcos; ?>  
</table>
```  

<a name="T5" id="T5"></a>

## Saratoga Base-USA set up

If you are using Saratoga Base-USA V3, you can download a ZIP file of updated items that will contain the necessary code and files at [PHP/AJAX Website Template Set - Updates](https://saratoga-weather.org/wxtemplates/updates.php#updates).  
Otherwise you will need to manually enter all of the following code below.

**Settings.php**  
The Settings.php file needs to have some code inserted to use the nws-alerts program in other web pages.  
A switch is provided to use the nws-alerts or the default atom-top-warning/atom-advisory program.  
The location(s) and the related codes are configured here also. This will override the locations and codes  
You can see where this is placed here: [PHP/AJAX Website Template Set - Settings.php - Base-USA](https://saratoga-weather.org/wxtemplates/Settings-config-USA.php)

Open Settings.php file.  
Copy the code below and paste it in the Settings.php file.

```php
// NWS Alerts package configuration (for Curly's nws-alerts scripts)  
$SITE['NWSalertsSidebar'] = true; // =true to insert in menubar, =false no insert to menubar  

$SITE['NWSalertsCodes'] = array(  
"ELKHART|INZ005|INC039",  
"ST JOE IN|INZ004|INC141",  
"ST JOE MI|MIZ079|MIC149",  
"BRANCH Co|MIZ080|MIC023"  
);  
```

Change the locations and Zone/County codes for the areas you want to cover. These codes can be found at the NWS web site. [https://alerts.weather.gov/](https://alerts.weather.gov/)

NOTE: If a Zone code is the first code entered, the Leaflet map will display in the details page. If a County code is the first code entered, the Leaflet map will not display.

NOTE: The FIRST code entered after the county name can not be repeated or used in another location. Any code after the first code can be used again for a different county.

After you have made the changes, save the file and close it.

**header.php**  
Open the file header.php located in the Saratoga Base-USA folder.  
Locate the following three lines:

```php
require_once("common.php");  
############################################################################  
if (isset($SITE['uomTemp']) ) {
```  

Replace those three lines with these:

```php
require_once("common.php");  
// add support for noCron=true fetch of nws-alerts to get current alerts  
if(isset($SITE['NWSalertsCodes']) and count($SITE['NWSalertsCodes']) > 0) {  
include_once("nws-alerts-config.php"); // load the configuration for nws-alerts  
if(isset($noCron) and $noCron) {  
print "<!-- nws-alerts noCron=true .. running nws-alerts.php inline -->\n";  
include_once("nws-alerts.php");  
}  
}  
############################################################################  
if (isset($SITE['uomTemp']) ) {  
```

Save and close that file.

**menubar.php**  
Open the menubar.php file.  
Find the following two lines:

```php
?>  
<!-- external links -->  
```

Replace those two lines with these:

```php
?>  
<?php if(  
isset($SITE['NWSalertsSidebar']) and $SITE['NWSalertsSidebar'] and  
isset($SITE['NWSalertsCodes']) and count($SITE['NWSalertsCodes']) > 0) { ?>  
<!-- nws-alerts icons -->  
<p class="sideBarTitle" style="text-align:center"><?php langtrans('Alerts'); ?></p>  
<?php  
include_once("nws-alerts-config.php"); // include the config file  
include($cacheFileDir.$iconFileName); // include the big icon file  
// construct menu bar icons  
$bigIcos = '<div style="text-align:center">'."\n";  
foreach($bigIcons as $bigI) {  
$bigIcos .= $bigI;  
}  
$bigIcos .= " <br />\n</div>\n<!-- end nws-alerts icons -->\n";  
echo $bigIcos; ?>  
<?php } // end of NWS alerts sidebar ?>  
<!-- end nws-alert icons-->  
<!-- external links -->  
```

Save the file and close it.

**wxindex.php**  
Open the file wxindex.php located in the Saratoga Base-USA folder.  
Find the following lines:

```php
<?php // insert desired warning box at top of page  
if ($useTopWarning) {  
if (phpversion() < 5.0) {  
include_once("rss-top-warning.php");  
} else {  
include_once("atom-top-warning.php");  
}  
} else {  
print " <div class=\"advisoryBox\">\n";  
$_REQUEST['inc'] = 'y';  
$_REQUEST['summary'] = 'Y';  
if (phpversion() < 5.0) {  
include_once("rss-advisory.php");  
} else {  
include_once("atom-advisory.php");  
}  
print " </div>\n";  
}  
?>  
```

Replace those lines with these lines:

```php
<?php // insert desired warning box at top of page  
if(isset($SITE['NWSalertsCodes']) and count($SITE['NWSalertsCodes']) > 0) {  
// Add nws-alerts alert box cache file  
include_once("nws-alerts-config.php");  
include($cacheFileDir.$aboxFileName);  
// Insert nws-alerts alert box  
echo $alertBox;  
?>  
<?php  
} else { // use atom scripts of choice  
if ($useTopWarning) {  
include_once("atom-top-warning.php");  
} else {  
print " <div class=\"advisoryBox\">\n";  
$_REQUEST['inc'] = 'y';  
$_REQUEST['summary'] = 'Y';  
include_once("atom-advisory.php");  
print " </div>\n";  
}  
}  
?>
```

Save the file and close it.

Upload these four files that were just edited.

**Adding** **Menu Items**  
Open the file _flyout-menu.xml_.  
_Be careful when editing this page!_  
XML is very finicky and it will cause the page not to load if it contains any errors.  
The examples below are using the file names entered in the nws-alerts-config.php file at $summaryURL .

To add the RSS Feed to the menu, copy the following line and insert it where you want it to appear:

```
<item caption="RSS Feed" link="nws-rssfeed.xml"/>
```

Save and close the file.  
You should delete the file flyout-menu.xml that is on the web server and then upload the modified file.

<a name="T6" id="T6"></a>

## Troubleshooting

**PHP errors:**  
The most common cause of PHP errors are the file and folder permission level settings. It will have the word "permission" in the error message. In this case, you will need to adjust the folder and file permission levels.

"...failed to open stream: No such file or directory..." -  A folder or file is not set and needs to be created. Also this could be a file permission setting not allowing the file to be written.

**Data doesn't update:**  
    • The NWS servers are down.  [Reference](#T10)  
    • The nws-alerts.php file is not included in a page or is not being cronned. [Reference](#T4)  
    • Your web host doesn't support cURL functions.  
    • Manually run the nws-alerts.php file and then view the page source. [Reference](#T12)  
      Comments will detail the time it took to cull the data and what happened to the cache file.  

**Leaflet map:**  
It does not display -  
    • Javascript is not enabled.  
    • Polygon coordinates are not provided in the alert.  
    • $displaymap is not set to '1' in the file _nws-alerts-config.php_.  
    • A zone code for a location is not in the _nws-all-zones-inc.php_ file.  
      This file is generated from data supplied by the NWS and sometimes, not all locations are listed.

The legend shows more alerts than the outlined areas:  
    • Alerts are overlapping with the same shape.

You have to zoom out to see the outlined shape of the alert:  
    • The Zone code is not near the County code.  
       Set **$zoomLevel**  to a lower number to zoom out further.
