<?php

// Boring stuff..
define('IN_MYBB', 1);

$templatelist = 'mybbmessenger_main, mybbmessenger_chatlist, mybbmessenger_buddylist';
require_once './global.php';

//Errors
if ((int) $mybb->user['uid'] < 1) {
	error_no_permission();
}


//Information
$uid = (int)$mybb->user['uid'];

//Runs when chat window gets opened of someone
if(isset($mybb->input['action']) && $mybb->input['action']=="request" && $mybb->input['toid']!="")
{
	$toid = (int)$mybb->input['toid'];
	$fromid = (int)$uid;
	$curr = date("Y-m-d H:i:s");

	//User Username & Avatar of a chatting user
	$query = $db->simple_select("users","*","uid='$toid'");
	$row = $db->fetch_array($query);
	$username = $row['username'];
	$avatar = $row['avatar'];
	if($avatar==NULL) {
		$avatar = "images/default_avatar.png";
	}

	//Check if we have previous chat with user
	$query = $db->simple_select("mybbmessenger_messages","*","(toid='$toid' AND fromid='$fromid') OR (toid='$fromid' AND fromid='$toid')");
	$rows = $db->num_rows($query);
	//If there is no previous chat, return new chat window data
	if($rows==0) {
		echo json_encode(array("response" => "Do Not Exist", "toid" => $toid, "fromid" => $fromid, "username" => $username, "avatar" => $avatar));
		exit;
	//Else, Let's dive deep
	} else {
		$tmpmsgst = "unread";
		//Get all the unread messages of the user from chatting user
		$query = $db->simple_select("mybbmessenger_messages","*","toid='$fromid' AND fromid='$toid' AND message_status='$tmpmsgst'");
		while($row=$db->fetch_array($query)) {
			$pid = (int)$row['id'];
			$msgstatus = 'read';
			$update_array = array(
				'message_status' => $msgstatus,
			);
			//Mark all unread messages as read now
			$db->update_query('mybbmessenger_messages',$update_array,"id='$pid'");
		}

		$chat_id_query = $db->simple_select("mybbmessenger_active_chat","*","uid='$uid'");
		$rows = $db->num_rows($chat_id_query);
		if($rows==0) {
			$insert_array = array('uid'=>$uid,'active_chat_id'=>$toid);
			$db->insert_query("mybbmessenger_active_chat",$insert_array);
		} else {
			$update_array = array('active_chat_id'=>$toid);
			$db->update_query("mybbmessenger_active_chat",$update_array,"uid='$uid'");
		}

		$query = $db->simple_select("mybbmessenger_relation","*","uid='$uid' AND toid='$toid'");
		$rows = $db->num_rows($query);
		if($rows>0) {
			$update_array = array(
				'recent_date' => $curr,
			);
			$db->update_query('mybbmessenger_relation',$update_array,"uid='$uid' AND toid='$toid'");
		} else {
			$update_array = array(
				'recent_date' => $curr,
			);
			$db->update_query('mybbmessenger_relation',$update_array,"uid='$toid' AND toid='$uid'");
		}

		//return some chat data to frontend
		echo json_encode(array("response" => "Exist", "avatar" => $avatar, "username" => $username));
		exit;
	}

//Runs when message is send
} elseif(isset($mybb->input['action']) && $mybb->input['action']=="messagesend" && $mybb->input['toid']!="" && $mybb->input['message']!="")
{

	$message = $mybb->input['message'];
	$message = trim(htmlentities(strip_tags($message)));
	$toid = (int)$mybb->input['toid'];


	$query = $db->simple_select("users","*","uid='$toid'");
	$row = $db->fetch_array($query);
	$username = $row['username'];
	$avatar = $row['avatar'];
	if($avatar==NULL) {
		$avatar = "images/default_avatar.png";
	}


	$dateline = TIME_NOW;
	$datenow = date("Y-m-d H:i:s");

	$chat_id_query = $db->simple_select("mybbmessenger_active_chat","*","uid='$uid'");
	$rows = $db->num_rows($chat_id_query);
	if($rows==0) {
		$insert_array = array('uid'=>$uid,'active_chat_id'=>$toid);
		$db->insert_query("mybbmessenger_active_chat",$insert_array);
	} else {
		$update_array = array('active_chat_id'=>$toid);
		$db->update_query("mybbmessenger_active_chat",$update_array,"uid='$uid'");
	}

	//check if users chatted before
	$sql = $db->simple_select("mybbmessenger_relation","*","(uid='$uid' AND toid='$toid') OR (uid='$toid' AND toid='$uid')");
	$rows = $db->num_rows($sql);
	//If not, create the relation for first time
	if($rows==0) {
		$insert_array = array(
			'uid' => (int)$uid,
			'toid' => (int)$toid,
			'recent_date' => $datenow,
			'lms' => $datenow,
		);
		$db->insert_query("mybbmessenger_relation",$insert_array);
	//Else let's get deeper
	} else {
		$query = $db->simple_select("mybbmessenger_relation","*","uid='$uid' AND toid='$toid'");
		$rows = $db->num_rows($query);
		if($rows>0) {
			$update_array = array(
				'recent_date' => $datenow,
				'lms' => $datenow,
			);
			$db->update_query("mybbmessenger_relation",$update_array,"uid='$uid' AND toid='$toid'");
		} else {
			$update_array = array(
				'recent_date' => $datenow,
				'lms' => $datenow,
			);
			$db->update_query("mybbmessenger_relation",$update_array,"uid='$toid' AND toid='$uid'");	
		}

	}

	//Check if there are unread message left
	$tempmsgst = "unread";
	$query = $db->simple_select("mybbmessenger_messages","*","toid='$uid' AND fromid='$toid' AND message_status='$tempmsgst'");
	while($row=$db->fetch_array($query)) {
		$pid = (int)$row['id'];
		$msgstatus = 'read';
		$update_array = array('message_status'=>$msgstatus);
		//Mark as read
		$db->update_query("mybbmessenger_messages",$update_array,"id='$pid'");
	}

	//Insert Message
	$msgstatus = "unread";
	$insert_array = array(
		'uid' => (int)$uid,
		'toid' => (int)$toid,
		'fromid' => (int)$uid,
		'message' => $db->escape_string($message),
		'dateline' => $dateline,
		'sentdate' => $datenow,
		'message_status' => $msgstatus,
	);
	$stm = $db->insert_query("mybbmessenger_messages",$insert_array);
	if($stm) {
		echo json_encode(array("response" => "success", "avatar" => $avatar, "username" => $username));
		exit;
	} else {
		echo json_encode(array("response" => "failure"));
		exit;
	}

//Runs on default page
} else {

	if($settings['mybbmessenger_enable'] != 1)
	{
		error("The messenger system is currently not active.");
	} else {
		$buddylist_row_each = '';
		$chatlist_row_each = '';
		$chatwindow_message = '';
		fill_buddylist($db,$mybb,$templates,$buddylist_row_each);
		fill_chatlist($db,$mybb,$templates,$chatlist_row_each);
		chat_window($db,$mybb,$templates,$chatwindow_message);
	}

}

function fill_buddylist($db,$mybb,$templates,&$buddylist_row_each)
{
	$user = $mybb->user;
	$uid = (int)$user['uid'];
	$fromuid = (int)$uid;
	$buddylistvar = $user['buddylist'];
	$buddylistvar = $db->escape_string($buddylistvar);
	if($buddylistvar==NULL) {
		$buddylistvar = 0;
	}
	$query = $db->simple_select("users","*","uid IN ($buddylistvar)",array("order_by"=>'lastactive',"order_dir"=>'DESC'));
	$rows = $db->num_rows($query);
	if($rows>0) {
		$timesearch = TIME_NOW;
		while($row=$db->fetch_array($query)) {
			$lastactive = $row['lastactive'];
			$lastactive = $timesearch-$lastactive;
			if($lastactive<900) {
				$buddy_status = 'images/online.png';
			} else {
				$buddy_status = 'images/offline.png';
			}

			if($lastactive<3600) {
				$lastactive = floor($lastactive/60)." Minutes Ago";
			} elseif($lastactive<86400) {
				$lastactive = floor($lastactive/3600)." Hours Ago";
			} else {
				$lastactive = my_date($mybb->settings['dateformat'], $row['lastactive']);
			}
			$buddy_username = $row['username'];
			$buddy_uid = $row['uid'];
			$buddy_avatar = $row['avatar'];
			if($buddy_avatar==NULL) {
				$buddy_avatar = "images/default_avatar.png";
			}
		    eval("\$buddylist_row_each .= \"".$templates->get("mybbmessenger_buddylist_row")."\";");
		}
	}
}


function fill_chatlist($db,$mybb,$templates,&$chatlist_row_each)
{
	$user = $mybb->user;
	$uid = (int)$user['uid'];
	$selfid = $uid;

	$query = $db->simple_select("mybbmessenger_relation","*","uid='$uid' OR toid='$uid'",array("order_by"=> 'lms',"order_dir"=>'DESC'));
	$rows = $db->num_rows($query);
	if($rows>0) {
		while($row=$db->fetch_array($query)) {
			$frommsgid = (int)$row['toid'];
			if($frommsgid==$uid) {
				$frommsgid = (int)$row['uid'];
			}

			$userquery = $db->simple_select("users","*","uid='$frommsgid'");
			$userrow = $db->fetch_array($userquery);
			$chatlistusername = $userrow['username'];
			$chatlistavatar = $userrow['avatar'];
			if($chatlistavatar==NULL) {
				$chatlistavatar = "images/default_avatar.png";
			}
			$mtimenow = TIME_NOW;
			$mlastactive = $mtimenow - $userrow['lastactive'];
			if($mlastactive<900) {
				$mstatus = 'images/online.png';
			}else {
				$mstatus = 'images/offline.png';
			}

			$messagequery = $db->simple_select("mybbmessenger_messages", "*", "(toid='$uid' AND fromid='$frommsgid') OR (toid='$frommsgid' AND fromid='$uid')", array("order_by"=>'id',"order_dir"=>"DESC","limit"=>1));
			$messagerow = $db->fetch_array($messagequery);
			$tempmsg = $messagerow['message'];
			$tempmsg = explode(" ", $tempmsg);
			$message_status = $messagerow['message_status'];
			$phtime = my_date($mybb->settings['timeformat'], $messagerow['dateline']);

			$forreadcheck = $messagerow['fromid'];
			if($forreadcheck==$uid) {
				$font_weight = 'normal';
				$font_color = '#aaa9ad';
				if($message_status=='read') {
					$r = " - Seen";
				} else {
					$r = " - Unseen";
				}
			} else {
				if($message_status==='read') {
					$font_weight = 'normal';
					$font_color = '#aaa9ad';
					$r = "";
				} else {
					$font_weight = 'bold';
					$font_color = '#666';
					$r = "";
				}
			}

			if(strpos($tempmsg[0], "-isfile") !== false) {
				$flt = strstr($tempmsg[0], "-isfile", true);
				$tempmsg[0] = "[image]";
			}

			if($tempmsg[2]!=NULL) {
				$tempmsg[2] = $tempmsg[2]."...";
			}

			if(strlen($tempmsg[0])>10 OR strlen($tempmsg[1])>10 OR strlen($tempmsg[2])>10) {
				$main_message = mb_substr($messagerow['message'],0, 20)."... - ".$phtime.$r;
			} else {
				$main_message = $tempmsg[0]." ".$tempmsg[1]." ".$tempmsg[2]." - ".$phtime.$r;
			}
			eval("\$chatlist_row_each .= \"".$templates->get("mybbmessenger_chatlist_row")."\";");	
		}

	}

}

function chat_window($db,$mybb,$templates,&$chatwindow_message)
{

	$user = $mybb->user;
	$uid = (int)$user['uid'];
	
	$query = $db->simple_select("mybbmessenger_active_chat","*","uid='$uid'");
	$row = $db->fetch_array($query);
	if($db->num_rows($query)==0) {
		$query = $db->simple_select("mybbmessenger_active_chat","*","active_chat_id='$uid'",array("order_by"=>'id',"order_dir"=>'DESC','limit'=>1));
		$row = $db->fetch_array($query);
		$recentchatid = (int)$row['uid'];
	} else {
		$recentchatid = (int)$row['active_chat_id'];
	}

	$query = $db->simple_select("users", "*", "uid='$recentchatid'");
	$row = $db->fetch_array($query);
	$pmhavatar = $row['avatar'];
	if($pmhavatar==NULL) {
		$pmhavatar = "images/default_avatar.png";
	}
	$pmhusername = $row['username'];

	$sql = $db->simple_select("mybbmessenger_messages", "*", "(toid='$recentchatid' AND fromid='$uid') OR (toid='$uid' AND fromid='$recentchatid')", array("order_by"=>'id'));
	$rows = $db->num_rows($sql);

	$mst = "read";

	$tempsql = $db->simple_select("mybbmessenger_messages", "*", "toid='$recentchatid' AND fromid='$uid' AND message_status='$mst'", array("order_by"=>'id','order_dir'=>'DESC','limit'=>1));
	$rowtemp = $db->fetch_array($tempsql);
	$lastunseen = $rowtemp['message_status'];
	$unseenpid = $rowtemp['id'];

	if($rows>0) {
		while($row = $db->fetch_array($sql)) {
			$message = $row['message'];
			$sent_date = $row['sent_date'];
			$fromid = $row['fromid'];
			$pid = $row['id'];

			//Smilies on chat			
			$query = $db->simple_select("smilies","*");
			while($drow = $db->fetch_array($query)) {
				$d = $drow['find'];
				if(strpos($message, $d) !==false ) {
					$imur = $drow['image'];
					$imrufull = '<img src="'.$imur.'">';
					$message = str_replace($d, $imrufull, $message);
				}
			}

			//images //unused on this ver
			// if(strpos($message, "-isfile") !== false) {
			// 	$flt = strstr($message, "-isfile", true);
			// 	$message = '<img src="{$flt}" class="imagepmsent">';
			// }

				$currentdate = date("Y-m-d H:i:s");
				$endtime = date('Y-m-d H:i:s', strtotime($sent_date. ' + 24 hours'));
				if($endtime<$currentdate) {
					$sentdate = $sent_date;
				} else {
					$sub = strtotime($currentdate)-strtotime($sent_date);
					$copmhour = floor($sub / 3600);
					if($copmhour<1) {
						$sentdate = floor($sub/60).' Minutes Ago';
					} else {
						$sentdate = floor($sub / 3600).' Hours Ago';
					}
				}



			if($uid==$fromid) {
				$alter_avatar = '';
				$pwholemsg_style = "position:relative";
				$pmhmsg_style = "background: rgb(0,132,255);color: #fff;float:right";
				if($unseenpid==$pid) {
					$seen_alter = '<div style="position:absolute;right:0;bottom: -22px"><img src="'.$pmhavatar.'" class="smimg"></div></div>';
				} else {
					$seen_alter = '</div>';
				}
			} else {
				$seen_alter = '</div>';
				$pwholemsg_style = "float:left";
				$pmhmsg_style = "";
				$alter_avatar = '<img src="'.$pmhavatar.'" class="pmhavatar" title="'.$pmhusername.'"/>';
			}
		eval("\$chatwindow_message .= \"".$templates->get("mybbmessenger_chatwindow_row")."\";");
		}

	}
}


$user = $mybb->user;
$uid = (int)$user['uid'];
$default_avatar = $user['avatar'];
if($default_avatar==NULL) {
	$default_avatar = "images/default_avatar.png";
}

$tmpstat = 'unread';
$query = $db->simple_select("mybbmessenger_messages","*","toid='$uid' AND message_status='$tmpstat'");
$uncountmsg = $db->num_rows($query);

//Display
$content = '';
eval("\$chatwindow = \"".$templates->get("mybbmessenger_chatwindow")."\";");
eval("\$buddylist = \"".$templates->get("mybbmessenger_buddylist")."\";");
eval("\$chatlist = \"".$templates->get("mybbmessenger_chatlist")."\";");
eval("\$content = \"" . $templates->get('mybbmessenger_main') . "\";");
output_page($content);