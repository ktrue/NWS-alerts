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
require_once("Settings.php");
require_once("common.php");
############################################################################
$TITLE= $SITE['organ'] . " - Watches/Warnings/Advisories";
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

<img src="//maps.wunderground.com/data/severe/current_severe_nostatefarm.gif?dontcache=y" width="630" height="480" border="0" alt="national advisories"/>

</div><!-- end main-copy -->

<?php
############################################################################
include("footer.php");
############################################################################
# End of Page
############################################################################
?>