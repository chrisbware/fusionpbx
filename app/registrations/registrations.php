<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists("registration_domain") || permission_exists("registration_all") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$search = check_str($_GET["search"]);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['header-registrations'];

//check permissions
	if (permission_exists("registration_domain") || permission_exists("registration_all") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the HTTP values and set as variables
	$profile = trim($_REQUEST["profile"]);
	$search = trim($_REQUEST["search"]);
	$show = trim($_REQUEST["show"]);
	if ($show == "all") { $profile = 'all'; }

//set the registrations variable
	$registrations = $_REQUEST["registrations"];

//get the action and remove items from the array that are not checked
	if (is_array($registrations)) {
		$x = 0;
		foreach ($registrations as $row) {
			//get the action
				switch ($row['action']) {
				    case "unregister":
				        $action = 'unregister';
				        break;
				    case "provision":
				        $action = 'provision';
				        break;
				        break;
				    case "reboot":
				        $action = 'reboot';
				        break;
				}
			//unset rows that were not selected
				if (!isset($row['checked'])) {
					unset($registrations[$x]);
				}
			//increment the id
				$x++;
		}
	}

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//user registrations
	if (isset($action)) {
		if (is_array($registrations)) {
			foreach ($registrations as $row) {
					if ($fp) {
						//get the vendor
							$vendor = device::get_vendor_by_agent($row['agent']);
						//prepare the command
							if ($action == "unregister") {
								$command = "sofia profile ".$row['profile']." flush_inbound_reg ".$row['user']." reboot";
							}
							if ($action == "provision") {
								$command = "lua app.lua event_notify ".$row['profile']." check_sync ".$row['user']." ".$vendor;
							}
							if ($action == "reboot") {
								$command = "lua app.lua event_notify ".$row['profile']." reboot ".$row['user']." ".$vendor;
							}
						//send the command
							$response = event_socket_request($fp, "api ".$command);
							$response = event_socket_request($fp, "api log notice ".$command);
					}
			}
		}
	}

//show the response
	if (isset($response)) {
		messages::add($text['label-event']." ".ucwords($cmd)."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$text['label-response'].htmlentities($response));
	}

//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the error message or show the content
	if (strlen($msg) > 0) {
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['label-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {

		//get the registrations
			$obj = new registrations;
			$registrations = $obj->get($profile);

		//count the registrations
			$registration_count = 0;
			if (is_array($registrations)) {
				foreach ($registrations as $row) {
					$matches = preg_grep ("/$search/i",$row);
					if ($matches != FALSE) {
						$registration_count++;
					}
				}
			}

		//define the checkbox_toggle function
			echo "<script type=\"text/javascript\">\n";
			echo "	function checkbox_toggle(item) {\n";
			echo "		var inputs = document.getElementsByTagName(\"input\");\n";
			echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
			echo "		    if (inputs[i].type === 'checkbox') {\n";
			echo "		       	if (document.getElementById('checkbox_all').checked == true) { \n";
			echo "				inputs[i].checked = true;\n";
			echo "			}\n";
			echo "				else {\n";
			echo "					inputs[i].checked = false;\n";
			echo "				}\n";
			echo "			}\n";
			echo "		}\n";
			echo "	}\n";
			echo "</script>\n";

		//show the registrations
			echo "<form method='post' action=''>\n";
			echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr>\n";
			echo "<td width='100%'>\n";
			echo "	<b>".$text['header-registrations']." (".$registration_count.")</b>\n";
			echo "</td>\n";
			echo "<td valign='middle' nowrap='nowrap' style='padding-right: 15px'>";
			//echo "		<form method='get' action=''>\n";
			echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
			echo "				<input type='hidden' name='show' value='".$show."'>";
			echo "				<input type='hidden' name='profile' value='".$sip_profile_name."'>";
			echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
			//echo "		</form>\n";
			echo "</td>";
			echo "<td valign='top' nowrap='nowrap'>";
			if (permission_exists('registration_all')) {
				if ($show == "all") {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='registrations.php?profile=$sip_profile_name'\" value='".$text['button-back']."'>\n";
				}
				else {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-show_all']."' onclick=\"window.location='registrations.php?profile=$sip_profile_name&show=all'\" value='".$text['button-show_all']."'>\n";
				}
			}
			echo "	<input type='button' class='btn' name='' alt='".$text['button-refresh']."' onclick=\"window.location='registrations.php?search=$search&show=$show'\" value='".$text['button-refresh']."'>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "<br />\n";

			echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr>\n";
			echo "	<th>\n";
			echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
			echo "	</th>\n";
			echo "	<th>".$text['label-user']."</th>\n";
			echo "	<th>".$text['label-agent']."</th>\n";
			echo "	<th>".$text['label-contact']."</th>\n";
			echo "	<th>".$text['label-lan_ip']."</th>\n";
			echo "	<th>".$text['label-ip']."</th>\n";
			echo "	<th>".$text['label-port']."</th>\n";
			echo "	<th>".$text['label-hostname']."</th>\n";
			echo "	<th>".$text['label-status']."</th>\n";
			echo "	<th>".$text['label-ping']."</th>\n";
			echo "	<th>".$text['label-sip_profile_name']."</th>\n";		
			echo "	<th>".$text['label-tools']."&nbsp;</th>\n";
			echo "</tr>\n";

		//order the array
			require_once "resources/classes/array_order.php";
			$order = new array_order();
			$registrations = $order->sort($registrations, 'sip-auth-realm', 'user');

		//display the array
			if (is_array($registrations)) {
				$x = 0;
				foreach ($registrations as $row) {
					//search 
						$matches = preg_grep ("/$search/i",$row);
						if ($matches != FALSE) {
							//set the user agent
								$agent = $row['agent'];
	
							//set the user id
								$user_id = str_replace('@', '_', $row['user']);

							//show the registrations
								echo "<tr>\n";
								echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 3px 0px 8px;'>\n";
								echo "		<input type='checkbox' name=\"registrations[$x][checked]\" id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$row['user']."').checked = false; }\">\n";
								echo "		<input type='hidden' name=\"registrations[$x][user]\" value='".$row['user']."' />\n";
								echo "		<input type='hidden' name=\"registrations[$x][profile]\" value='".$row['sip_profile_name']."' />\n";
								echo "		<input type='hidden' name=\"registrations[$x][agent]\" value='".$row['agent']."' />\n";
								echo "		<input type='hidden' name=\"registrations[$x][domain]\" value='".$row['sip-auth-realm']."' />\n";
								echo "	</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['user']."&nbsp;</td>\n";
								echo "	<td class='".$row_style[$c]."'>".htmlentities($row['agent'])."&nbsp;</td>\n";
								echo "	<td class='".$row_style[$c]."'>".explode('"',$row['contact'])[1]."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['lan-ip']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['network-ip']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['network-port']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['host']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['status']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['ping-time']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['sip_profile_name']."</td>\n";
								echo "	<td class='".$row_style[$c]."' style='text-align: right;' nowrap='nowrap'>\n";
								echo "		<button type='submit' class='btn-default' name=\"registrations[$x][action]\" value='unregister'>".$text['button-unregister']."</button>\n";
								echo "		<button type='submit' class='btn-default' name=\"registrations[$x][action]\" value='provision'>".$text['button-provision']."</button>\n";
								echo "		<button type='submit' class='btn-default' name=\"registrations[$x][action]\" value='reboot'>".$text['button-reboot']."</button>\n";
								echo "	</td>\n";
								echo "</tr>\n";
								if ($c==0) { $c=1; } else { $c=0; }
								$x++;
						}
				}
			}
			echo "</table>\n";
			echo "<input type='hidden' name=\"show\" value='".$show."' />\n";
			echo "</form>\n";

		//close the connection and unset the variable
			fclose($fp);
			unset($xml);
	}

//get the footer
	require_once "resources/footer.php";

?>
