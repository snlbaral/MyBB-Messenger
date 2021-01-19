<?php
//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}

$plugins->add_hook('global_start', 'mybbmessenger_start');

//Plugin Information
function mybbmessenger_info()
{
	return array(
		'name' => 'MyBB Messenger',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugins allows mybb forum users to have private live chat',
		'version' => '1.0',
		'compatibility' => '18*',
		'guid' => '',
	);
}

//Plugin Installation
function mybbmessenger_install()
{
	global $db;
	$collation = $db->build_create_table_collation();
	if (!$db->table_exists('mybbmessenger_messages')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "mybbmessenger_messages(
                        id serial,
                        uid int NOT NULL,
                        toid int NOT NULL,
                        fromid int NOT NULL,
                        message varchar(255) NOT NULL,
                        dateline int NOT NULL,
                        sentdate timestamp NOT NULL,
                        message_status varchar(100) NOT NULL DEFAULT 'unseen',
                        PRIMARY KEY (id)
                    );"
                );
                break;
            default:
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "mybbmessenger_messages(
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(10) unsigned NOT NULL,
                        `toid` int(10) unsigned NOT NULL,
                        `fromid` int(10) unsigned NOT NULL,                        
                        `message` varchar(255) NOT NULL,
                        `dateline` int NOT NULL,
                        `sentdate` datetime NOT NULL,  
                        `message_status` varchar(100) NOT NULL DEFAULT 'unseen',
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM{$collation};"
                );
                break;
        }
	}

	if (!$db->table_exists('mybbmessenger_relation')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "mybbmessenger_relation(
                        id serial,
                        uid int NOT NULL,
                        toid int NOT NULL,
                        recent_date timestamp NOT NULL,
                        active_chat varchar(100) default(0),
                        lms timestamp NOT NULL,
                        PRIMARY KEY (id)
                    );"
                );
                break;
            default:
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "mybbmessenger_relation(
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(10) unsigned NOT NULL,
                        `toid` int(10) unsigned NOT NULL,
                        `recent_date` datetime NOT NULL, 
                        `active_chat` varchar(100) default(0), 
                        `lms` datetime NOT NULL,  
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM{$collation};"
                );
                break;
        }
	}


	if (!$db->table_exists('mybbmessenger_active_chat')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "mybbmessenger_active_chat(
                        id serial,
                        uid int NOT NULL,
                        active_chat_id varchar(100) default(0),
                        PRIMARY KEY (id)
                    );"
                );
                break;
            default:
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "mybbmessenger_active_chat(
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(10) unsigned NOT NULL,
                        `active_chat_id` varchar(100) default(0), 
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM{$collation};"
                );
                break;
        }
	}

}


function mybbmessenger_is_installed()
{
	global $db;
	return $db->table_exists('mybbmessenger_messages');
}

//Plugin Uninstall
function mybbmessenger_uninstall()
{
	global $db;
	if ($db->table_exists('mybbmessenger_messages')) {
		$db->drop_table('mybbmessenger_messages');
	}
	if ($db->table_exists('mybbmessenger_relation')) {
		$db->drop_table('mybbmessenger_relation');
	}
	if ($db->table_exists('mybbmessenger_active_chat')) {
		$db->drop_table('mybbmessenger_active_chat');
	}
}

function mybbmessenger_activate()
{
	global $db, $mybb, $settings;

	//Admin CP Settings
	$mybbmessenger_group = array(
		'gid' => '',
		'name' => 'mybbmessenger',
		'title' => 'MyBB Users Private Chat Plugin',
		'description' => 'Settings for MyBB Users Private Chat Plugin',
		'disporder' => '1',
		'isdefault' =>  '0',
	);
	$db->insert_query('settinggroups',$mybbmessenger_group);
	$gid = $db->insert_id();
	//Enable or Disable
	$mybbmessenger_enable = array(
		'sid' => 'NULL',
		'name' => 'mybbmessenger_enable',
		'title' => 'Do you want to enable this plugin?',
		'description' => 'If you set this option to yes, this plugin will start working.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);
	$db->insert_query('settings',$mybbmessenger_enable);
	rebuild_settings();


	$q = $db->simple_select("templategroups", "COUNT(*) as count", "title = 'MyBB Messenger'");
	$c = $db->fetch_field($q, "count");
	$db->free_result($q);
	
	if($c < 1)
	{
		$ins = array(
			"prefix"		=> "mybbmessenger",
			"title"			=> "MyBB Messenger",
		);
		$db->insert_query("templategroups", $ins);
	}


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_main',
		'template' => $db->escape_string('
{$headerinclude}
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="inc/plugins/MybbStuff/mybbmessenger/mybbmessenger.css">
<script type="text/javascript" src="jscripts/messenger.js"></script>
<div class="unreadmsg" style="display: none">{$uncountmsg}</div>
<div class="bdy">
	<div id="chatlist_main">
		<div class="chatTitle">
			<div id="dum">
				<span style="position: absolute;right: 7%;bottom: 13px" onclick="return returnToList();"><i class="fa fa-arrow-circle-left" aria-hidden="true"></i></span>
			</div>
			<div class="chatTab" id="chatTab" onclick="return chatTab();">
				<img src="{$default_avatar}"><span>Chats</span>
			</div>
			<div class="activeTab" id="activeTab" onclick="return activeTab();">Active</div>	
		</div>
		{$chatwindow}
		<div id="chatlist_tabs">
			<div id="tabs">
				{$chatlist}
				{$buddylist}
			</div>
		</div>
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_chatlist',
		'template' => $db->escape_string('
<div class="tab1ChatList" id="tab1ChatList">
{$chatlist_row_each}
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_chatlist_row',
		'template' => $db->escape_string('
<div class="chatlistdiv" onclick="return oChatHead(this);" toid="{$frommsgid}" fromid="{$selfid}">
	<div class="chat_avt">
		<img src="{$chatlistavatar}" class="chatlistimg">
		<img src="{$mstatus}" class="buddy_status2">
	</div>
	<div class="chat_msg">	
		<div class="chatlistusername">
			{$chatlistusername}
		</div>
		<span style="word-break: break-word;font-weight:{$font_weight};color:{$font_color}">
			{$main_message}
		</span>
	</div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_buddylist',
		'template' => $db->escape_string('
<div class="tab2BuddyList" id="tab2BuddyList">
	<span class="buddylist_infotext">Add '.$mybb->settings["bbname"].' members to your buddylist to chat with them</span>
	{$buddylist_row_each}
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_buddylist_row',
		'template' => $db->escape_string('
<div class="buddylist_whole">
	<a style="cursor: pointer;" onclick="return oChatHead(this);" fromid="{$fromuid}" toid="{$buddy_uid}">
		<div class="buddy_avatar">
			<img src="{$buddy_avatar}"/>
			<img class="buddy_status" src="{$buddy_status}"/>
		</div>
		<div class="buddy_username">
			{$buddy_username}<br><span style="display:block;font-size: 8px;color: #aaa9ad;margin-top:2px">{$lastactive}</span>
		</div>
	</a>
</div>
<br/>
'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_chatwindow',
		'template' => $db->escape_string('
<div id="pChat">
	<div id="pMainHead">
		<div id="pChild">
			<div id="secondChild">
				{$chatwindow_message}
			</div>
		</div>
		<form autocomplete="off" method="POST" action="" id="pmhForm">
			<label for="pmsendfile" name="f2" id="f2"></label>
			<input type="text" name="message" id="pmhMessage">
			<input type="hidden" name="action" value="messagesend">
		<input type="image" src="images/pmsub.png" name="submit" alt="submit" id="pmhFormbtn">
		</form>
	</div>
	<div id="pAHead"></div>
</div>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_chatwindow_row',
		'template' => $db->escape_string('
<div class="pwholemsg" style="{$pwholemsg_style}">
	{$alter_avatar}
	<div class="pmhmsg" style="{$pmhmsg_style}" title="{$sentdate}">
		{$message}
	</div>
	<div style="display:none" id="recentchatid">{$recentchatid}</div>
{$seen_alter}
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'mybbmessenger_iframe',
		'template' => $db->escape_string('
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<style>
#messengerFrame {
	height: 490px;
	position: fixed;
	right: 2%;
	border: 0;
	z-index: 99999;
	border-radius: 6px;
	border: 1px solid #999;
	bottom: 5px;
	width: 330px;
	display: none;
}
#messengerClose, #messengerOpen {
	position: fixed;
	right: 2%;
	z-index: 999;
	background: #5876ab;
	padding: 6px;
	text-align: center;
	color: #fff;
	cursor: pointer;
	border-radius: 50%;
	border: 2px solid #fff;
	bottom: 495px;
}

#messengerClose {
	display: none;
}

#unreadmsg {
    color: #fff !important;
    position: absolute;
    z-index: 999;
    top: -13px;
    background: green;
    width: 19px;
    border-radius: 50%;
    height: 19px;
    right: -13px;
    border: 2px solid #fff;
    line-height: 19px;
    font-size: 12px;
    display: none;
}
</style>

<div id="messengerClose">
<i class="fa fa-times" aria-hidden="true"></i>
</div>
<div id="messengerOpen">
<span id="unreadmsg"></span><i class="fa fa-comment" aria-hidden="true"></i>
</div>
<iframe src="'.$mybb->settings['bburl'].'/messenger.php" id="messengerFrame"></iframe>

<script>
document.getElementById("messengerFrame").onload = function() {
	var unframe = document.getElementById("messengerFrame");
	var undoc = unframe.contentDocument;
	var unbody = undoc.body;
	var unelm = unbody.getElementsByClassName("unreadmsg")[0];
	document.getElementById("unreadmsg").innerHTML = unelm.innerHTML;
	var uncom = unelm.innerHTML;
	if(uncom>0) {
		document.getElementById("unreadmsg").style.display = "block";
	} else {
		document.getElementById("unreadmsg").style.display = "none";
	}
}

document.getElementById("messengerOpen").onclick = function() {
	document.getElementById("messengerOpen").style.display = "none";
	document.getElementById("messengerClose").style.display = "initial";
	document.getElementById("messengerFrame").style.display = "initial";
}

document.getElementById("messengerClose").onclick = function() {
	document.getElementById("messengerOpen").style.display = "initial";
	document.getElementById("messengerClose").style.display = "none";
	document.getElementById("messengerFrame").style.display = "none";
}
</script>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Activate in header template
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote("{\$awaitingusers}") . "#i", "{\$awaitingusers}\r\n
        {\$mybbmessenger}");

}

//Deactivate Plugin
function mybbmessenger_deactivate()
{
	global $db, $mybb, $settings;

	//Templates Delete
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('mybbmessenger_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('mybbmessenger')");
	$db->query("DELETE from ".TABLE_PREFIX."templategroups WHERE prefix IN ('mybbmessenger')");
	$db->query("DELETE from ".TABLE_PREFIX."templates WHERE title LIKE 'mybbmessenger%'");
	rebuild_settings();

	//Deactive from header template
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote("\r\n
        {\$mybbmessenger}") . "#i", "", 0);

}


function mybbmessenger_start()
{
	global $db, $mybb, $settings, $lang, $templates, $mybbmessenger;
	if($settings['mybbmessenger_enable']!=1 OR $mybb->user['usergroup']==1) {
	} else {
		eval("\$mybbmessenger = \"".$templates->get("mybbmessenger_iframe")."\";");
	}

}