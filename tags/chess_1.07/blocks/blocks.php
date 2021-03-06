<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

/**
 * Chess module blocks
 *
 * @package chess
 * @subpackage blocks
 *
 * @see $modversion['blocks'] in xoops_version.php
 */

/**#@+
 */
require_once XOOPS_ROOT_PATH . '/modules/chess/include/constants.inc.php';
require_once XOOPS_ROOT_PATH . '/modules/chess/include/functions.inc.php';
/**#@-*/

/**
 * Generate Smarty template variables for Recent Games block.
 *
 * @param array $options
 * @return array
 */
function b_chess_games_show($options)
{
	global $xoopsModule, $xoopsDB;

	// don't display this block within owning module
	if (is_object($xoopsModule) and $xoopsModule->getVar('dirname') == 'chess') {
		return array();
	}

	$table = $xoopsDB->prefix('chess_games');

	$limit = intval($options[0]); // sanitize with intval()

	$where = 'white_uid != black_uid';
	switch($options[1]) {
		case 1:
			$where .= " AND pgn_result = '*'";
			break;
		case 2:
			$where .= " AND pgn_result != '*'";
			break;
	}
	if ($options[2] == 1) {
		$where .= " AND is_rated = '1'";
	}

	$result = $xoopsDB->query(trim("
		SELECT   game_id, fen_active_color, white_uid, black_uid, pgn_result, UNIX_TIMESTAMP(create_date) AS create_date,
		         UNIX_TIMESTAMP(start_date) AS start_date, UNIX_TIMESTAMP(last_date) AS last_date,
		         UNIX_TIMESTAMP(GREATEST(create_date,start_date,last_date)) AS most_recent_date
		FROM     $table
		WHERE    $where
		ORDER BY most_recent_date DESC
		LIMIT    $limit
	"));

	// user IDs that will require mapping to usernames
	$userids = array();

	$games = array();

 	while ($row = $xoopsDB->fetchArray($result)) {

		$games[] = array(
			'game_id'          => $row['game_id'],
			'white_uid'        => $row['white_uid'],
			'black_uid'        => $row['black_uid'],
			'date'             => $row['most_recent_date'],
			'fen_active_color' => $row['fen_active_color'],
			'pgn_result'       => $row['pgn_result'],
		);

		// save user IDs that will require mapping to usernames
		if ($row['white_uid']) {
			$userids[$row['white_uid']] = 1;
		}
		if ($row['black_uid']) {
			$userids[$row['black_uid']] = 1;
		}
	}

	$xoopsDB->freeRecordSet($result);

	// get mapping of user IDs to usernames
	$member_handler =& xoops_gethandler('member');
	$criteria       =  new Criteria('uid', '(' . implode(',', array_keys($userids)) . ')', 'IN');
	$usernames      =  $member_handler->getUserList($criteria);

	// add usernames to $games
	foreach ($games as $k => $game) {
		$games[$k]['username_white'] = isset($usernames[$game['white_uid']]) ? $usernames[$game['white_uid']] : '?';
		$games[$k]['username_black'] = isset($usernames[$game['black_uid']]) ? $usernames[$game['black_uid']] : '?';
	}

	$block['games'] = $games;

	$block['date_format'] = _SHORTDATESTRING;

	return $block;
}

/**
 * Generate Smarty template variables for Recent Challenges block.
 *
 * @param array $options
 * @return array
 */
function b_chess_challenges_show($options)
{
	global $xoopsModule, $xoopsDB;

	// don't display this block within owning module
	if (is_object($xoopsModule) and $xoopsModule->getVar('dirname') == 'chess') {
		return array();
	}

	$table = $xoopsDB->prefix('chess_challenges');

	$limit = intval($options[0]); // sanitize with intval()

	switch($options[1]) {
		case 1:
			$where = "game_type = 'open'";
			break;
		case 2:
			$where = "game_type = 'user'";
			break;
		default:
			$where = 1;
			break;
	}

	$result = $xoopsDB->query(trim("
		SELECT   challenge_id, game_type, player1_uid, player2_uid, UNIX_TIMESTAMP(create_date) AS create_date
		FROM     $table
		WHERE    $where
		ORDER BY create_date DESC
		LIMIT    $limit
	"));

	// user IDs that will require mapping to usernames
	$userids = array();

	$challenges = array();

 	while ($row = $xoopsDB->fetchArray($result)) {

		$challenges[] = array(
			'challenge_id' => $row['challenge_id'],
			'game_type'    => $row['game_type'],
			'player1_uid'  => $row['player1_uid'],
			'player2_uid'  => $row['player2_uid'],
			'create_date'  => $row['create_date'],
		);

		// save user IDs that will require mapping to usernames
		if ($row['player1_uid']) {
			$userids[$row['player1_uid']] = 1;
		}
		if ($row['player2_uid']) {
			$userids[$row['player2_uid']] = 1;
		}
	}

	$xoopsDB->freeRecordSet($result);

	// get mapping of user IDs to usernames
	$member_handler =& xoops_gethandler('member');
	$criteria       =  new Criteria('uid', '(' . implode(',', array_keys($userids)) . ')', 'IN');
	$usernames      =  $member_handler->getUserList($criteria);

	// add usernames to $challenges
	foreach ($challenges as $k => $challenge) {
		$challenges[$k]['username_player1'] = isset($usernames[$challenge['player1_uid']]) ? $usernames[$challenge['player1_uid']] : '?';
		$challenges[$k]['username_player2'] = isset($usernames[$challenge['player2_uid']]) ? $usernames[$challenge['player2_uid']] : '?';
	}

	$block['challenges'] = $challenges;

	$block['date_format'] = _SHORTDATESTRING;

	return $block;
}

/**
 * Generate Smarty template variables for Highest-rated Players block.
 *
 * @param array $options
 * @return array
 */
function b_chess_players_show($options)
{
	global $xoopsModule, $xoopsDB;

	// don't display this block within owning module
	if (is_object($xoopsModule) and $xoopsModule->getVar('dirname') == 'chess') {
		return array();
	}

	require_once XOOPS_ROOT_PATH . '/modules/chess/include/ratings.inc.php';

	$module_handler =& xoops_gethandler('module');
	$module         =& $module_handler->getByDirname('chess');
	$config_handler =& xoops_gethandler('config');
	$moduleConfig   =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));
	$block['rating_system']     = $moduleConfig['rating_system'];
	$block['provisional_games'] = chess_ratings_num_provisional_games();

	// if ratings disabled, nothing else to do
	if ($moduleConfig['rating_system'] == 'none') {
		return $block;
	}

	$table = $xoopsDB->prefix('chess_ratings');

	$limit = intval($options[0]); // sanitize with intval()

	switch($options[1]) {
		case 1:
			$block['show_provisional_ratings'] = false;
			$where = "(games_won+games_lost+games_drawn) >= '{$block['provisional_games']}'";
			break;
		case 2:
		default:
			$block['show_provisional_ratings'] = true;
			$where = 1;
			break;
	}

	$result = $xoopsDB->query(trim("
		SELECT   player_uid, rating, (games_won+games_lost+games_drawn) AS games_played
		FROM     $table
		WHERE    $where
		ORDER BY rating DESC, player_uid ASC
		LIMIT    $limit
	"));

	// user IDs that will require mapping to usernames
	$userids = array();

	$players = array();

 	while ($row = $xoopsDB->fetchArray($result)) {

		$players[] = array(
			'player_uid'   => $row['player_uid'],
			'rating'       => $row['rating'],
			'games_played' => $row['games_played'],
		);

		// save user IDs that will require mapping to usernames
		if ($row['player_uid']) {
			$userids[$row['player_uid']] = 1;
		}
	}

	$xoopsDB->freeRecordSet($result);

	// get mapping of user IDs to usernames
	if (!empty($userids)) {
		$member_handler =& xoops_gethandler('member');
		$criteria       =  new Criteria('uid', '(' . implode(',', array_keys($userids)) . ')', 'IN');
		$usernames      =  $member_handler->getUserList($criteria);
	}

	// add usernames to $players
	foreach ($players as $k => $player) {
		$players[$k]['player_uname'] = isset($usernames[$player['player_uid']]) ? $usernames[$player['player_uid']] : '?';
	}

	$block['players'] = $players;

	return $block;
}

/**
 * Generate HTML form fragment for editing settings of Recent Games block.
 *
 * @param array $options
 * @return string
 */
function b_chess_games_edit($options)
{
	$show_inplay     = $options[1] == 1 ? "checked='checked'" : '';
	$show_concluded  = $options[1] == 2 ? "checked='checked'" : '';
	$show_both       = $options[1] == 3 ? "checked='checked'" : '';

	$show_rated_only = $options[2] == 1 ? "checked='checked'" : '';
	$show_unrated    = $options[2] == 2 ? "checked='checked'" : '';

	$form = "
		"._MB_CHESS_NUM_GAMES.": <input type='text' name='options[0]' value='{$options[0]}' size='3' maxlength='3' />
		<br />
		<br />
		<input type='radio' name='options[1]' value='1' $show_inplay     /> "._MB_CHESS_SHOW_GAMES_INPLAY."
		<input type='radio' name='options[1]' value='2' $show_concluded  /> "._MB_CHESS_SHOW_GAMES_CONCLUDED."
		<input type='radio' name='options[1]' value='3' $show_both       /> "._MB_CHESS_SHOW_GAMES_BOTH."
		<br />
		<br />
		<input type='radio' name='options[2]' value='1' $show_rated_only /> "._MB_CHESS_SHOW_GAMES_RATED."
		<input type='radio' name='options[2]' value='2' $show_unrated    /> "._MB_CHESS_SHOW_GAMES_UNRATED."
	";

	return $form;
}

/**
 * Generate HTML form fragment for editing settings of Recent Challenges block.
 *
 * @param array $options
 * @return string
 */
function b_chess_challenges_edit($options)
{
	$show_open = $options[1] == 1 ? "checked='checked'" : '';
	$show_user = $options[1] == 2 ? "checked='checked'" : '';
	$show_both = $options[1] == 3 ? "checked='checked'" : '';

	$form = "
		"._MB_CHESS_NUM_CHALLENGES.": <input type='text' name='options[0]' value='{$options[0]}' size='3' maxlength='3' />
		<br />
		<input type='radio' name='options[1]' value='1' $show_open /> "._MB_CHESS_SHOW_CHALLENGES_OPEN."
		<input type='radio' name='options[1]' value='2' $show_user /> "._MB_CHESS_SHOW_CHALLENGES_USER."
		<input type='radio' name='options[1]' value='3' $show_both /> "._MB_CHESS_SHOW_CHALLENGES_BOTH."
	";

	return $form;
}

/**
 * Generate HTML form fragment for editing settings of Highest-rated Players block.
 *
 * @param array $options
 * @return string
 */
function b_chess_players_edit($options)
{
	$show_nonprovisional = $options[1] == 1 ? "checked='checked'" : '';
	$show_all            = $options[1] == 2 ? "checked='checked'" : '';

	$form = "
		"._MB_CHESS_NUM_PLAYERS.": <input type='text' name='options[0]' value='{$options[0]}' size='3' maxlength='3' />
		<br />
		<input type='radio' name='options[1]' value='1' $show_nonprovisional /> "._MB_CHESS_SHOW_NONPROVISIONAL."
		<input type='radio' name='options[1]' value='2' $show_all            /> "._MB_CHESS_SHOW_ALL_RATINGS."
	";

	return $form;
}

?>
