<?php
/**
* @package manifest file for Recount forum history stats
* @version 1.0
* @author Joker (http://www.simplemachines.org/community/index.php?action=profile;u=226111)
* @copyright Copyright (c) 2012, Siddhartha Gupta
* @license http://www.mozilla.org/MPL/MPL-1.1.html
*/

/*
* Version: MPL 1.1
*
* The contents of this file are subject to the Mozilla Public License Version
* 1.1 (the "License"); you may not use this file except in compliance with
* the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS" basis,
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
* for the specific language governing rights and limitations under the
* License.
*
* The Initial Developer of the Original Code is
*  Joker (http://www.simplemachines.org/community/index.php?action=profile;u=226111)
* Portions created by the Initial Developer are Copyright (C) 2012
* the Initial Developer. All Rights Reserved.
*
* Contributor(s):
*
*/

require_once('SSI.php');
if (!defined('SMF'))
	die('Hacking attempt...');

recountForumStats();

function recountForumStats() {
    global $context, $db_prefix;

	//Lets gather data first
    $request = db_query("
        SELECT DATE(FROM_UNIXTIME(m.posterTime)) AS for_date, COUNT(m.ID_MSG) as message_count, COUNT(DISTINCT t.ID_TOPIC) as topic_count
        FROM {$db_prefix}messages as m
		INNER JOIN {$db_prefix}topics AS t ON (t.ID_TOPIC = m.ID_TOPIC)
        GROUP BY DATE(FROM_UNIXTIME(m.posterTime))",
    __FILE__, __LINE__);
    $data = array();
	while ($row = mysql_fetch_assoc($request))
	{
		$data[$row['for_date']] = array(
            'message_count' => $row['message_count'],
            'topic_count' => $row['topic_count'],
        );
	}
	mysql_free_result($request);

	$request = db_query("
        SELECT DATE(FROM_UNIXTIME(mem.dateRegistered)) AS for_date, COUNT(mem.ID_MEMBER) as member_count
        FROM {$db_prefix}members as mem
        GROUP BY DATE(FROM_UNIXTIME(mem.dateRegistered))",
    __FILE__, __LINE__);
    $mem_data = array();
	while ($row = mysql_fetch_assoc($request))
	{
		$mem_data[$row['for_date']] = array(
            'member_count' => $row['member_count'],
        );
	}
	mysql_free_result($request);

	//Lets merge the arrays
	$mergeData = array();
	foreach($data as $key => $value) {
		if(array_key_exists($key, $mem_data)){
			$mergeData[$key] = array(
				'message_count' => $value['message_count'],
				'topic_count' => $value['topic_count'],
				'member_count' => $mem_data[$key]['member_count']
			);
			unset($mem_data[$key]);
		} else {
			$mergeData[$key] = array(
				'message_count' => $value['message_count'],
				'topic_count' => $value['topic_count'],
				'member_count' => 0
			);
		}
	}

	//lets add left out members
	foreach($mem_data as $key => $value) {
		$mergeData[$key] = array(
			'message_count' => 0,
			'topic_count' => 0,
			'member_count' => $value['member_count']
		);
	}

    foreach($mergeData as $key => $val) {
		$time = strtotime($key);
		$myDate = date('Y-m-d', $time);

        $request = db_query("
            REPLACE INTO {$db_prefix}log_activity (date, topics, posts, registers) values('$myDate', $val[topic_count], $val[message_count], $val[member_count])",
        __FILE__, __LINE__);
    }
}

//just for ref
//UPDATE smf_log_activity SET mostOn = CAST((RAND() * 30000)+1 AS UNSIGNED)

?>