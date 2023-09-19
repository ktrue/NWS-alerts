<?php
############################################################################
# A Project of TNET Services, Inc. and Saratoga-Weather.org (Base-USA template set)
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
//Version 1.01 - 28-Jul-2012 - integrated support for nws-alerts scripts
//Version 1.02 - 27-Jan-2018 - https fix for WU map
//Version 1.03 - 12-Apr-2020 - replaced WU image with weather.gov image
//Version 1.04 - 27-Dec-2022 - fixes for narrow aspect display
require_once("Settings.php");
require_once("common.php");
############################################################################
$TITLE= $SITE['organ'] . " - Watches/Warnings/Advisories";
$showGizmo = true;  // set to false to exclude the gizmo
include("top.php");
############################################################################
if(file_exists("NWS-advisory-legend-inc.php")) {
	include_once("NWS-advisory-legend-inc.php");
	print "<style type=\"text/css\">\n";
	print $legendCSS;
	print "</style>\n";
}
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
<?php if (isset($_SESSION['CSSwidescreen'])) { ?>
	<div id="main-copy" style="width:<?php echo ($_SESSION['CSSwidescreen']==1 ? 'calc(100%-114)' : 620) ?>px">
<?php } else { ?>
	<div id="main-copy" style="width:100%; padding:0px border:inset">
<?php }	?>
 
<?php // insert desired warning box at top of page

  if(isset($SITE['NWSalertsCodes']) and count($SITE['NWSalertsCodes']) > 0) {
    include_once("nws-alerts-summary-inc.php");  

  } else { // use atom scripts of choice
?> 
	  <h3>Watches, Warnings, and Advisories</h3> 
        
    <div class="advisoryBox" style="text-align: left; background-color:#FFFF99">
	<?php 
	   $_REQUEST['inc'] = 'y';
	   include("atom-advisory.php");
	 ?>
	</div>

<?php } // end nws-alerts / original atom alerts selection ?>

<img src="https://forecast.weather.gov/wwamap/png/US.png" width="620" height="388" border="0" alt="national advisories"/>

<?php if(isset($legendHTML)) {
	print $legendHTML;
} ?>
</div><!-- end main-copy -->

<?php
############################################################################
include("footer.php");
############################################################################
# End of Page
############################################################################
?>