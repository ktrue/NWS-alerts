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
//Version 1.00 - 28-Jul-2012 - initial relase with nws-alerts package
//Version 1.01 - 03-Aug-2016 - updated for V1.37 nws-alerts.php
//Version 1.02 - 14-May-2019 - updated for Leaflet/OpenStreetMaps
require_once("Settings.php");
require_once("common.php");
############################################################################
$TITLE = langtransstr($SITE['organ']) . " - " .langtransstr('NWS Alerts Details');
$showGizmo = true;  // set to false to exclude the gizmo
include("top.php");
############################################################################
?>
<link rel="stylesheet" href="nws-alerts.css" />
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
<h3> </h3>
<?php include("nws-alerts-details-inc.php"); ?>
</div><!-- end main-copy -->

<?php
############################################################################
include("footer.php");
############################################################################
# End of Page
############################################################################
?>