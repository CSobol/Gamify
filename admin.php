<?php
/************************************************************* 
 * This script is developed by Arturs Sosins aka ar2rsawseen, http://webcodingeasy.com 
 * Feel free to distribute and modify code, but keep reference to its creator 
 * 
 * Gamify class allows to implement game logic into PHP aplications. 
 * It can create needed tables for storing information on most popular database platforms using PDO. 
 * It also can add users, define levels and achievements and generate user statistics and tops.
 * Then it is posible to bind class functions to user actions, to allow them gain experience and achievements.
 * 
 * For more information, examples and online documentation visit:  
 * http://webcodingeasy.com/PHP-classes/Implement-game-logic-to-your-web-application
**************************************************************/
include("./gamify.php");
$g = new gamify("localhost", "root", "password", "gamify");
$g->debug();

//create new user
if(isset($_POST["new_user"]) && $_POST["new_user"] != "")
{
	$g->create_user($_POST["new_user"]);
}

//save edited user info
if(isset($_POST["save_user"]))
{
	$g->edit_user($_POST["save_user"], $_POST["save_username"], $_POST["save_experience"], $_POST["save_level"]);
}

//delete user
if(isset($_POST["del_user"]) && $_POST["del_user"] != "")
{
	$g->delete_user($_POST["del_user"]);
}

//create new level
if(isset($_POST["new_level"]) && $_POST["new_level"] != "")
{
	$g->create_level($_POST["new_level"], $_POST["new_level_exp"]);
}
//save edited level info
if(isset($_POST["save_level"]))
{
	$g->edit_level($_POST["save_level"], $_POST["save_name"], $_POST["save_experience"]);
}
//delete level
if(isset($_POST["del_level"]) && $_POST["del_level"] != "")
{
	$g->delete_level($_POST["del_level"]);
}


//create new achievement
if(isset($_POST["new_ach"]) && $_POST["new_ach"] != "")
{
	$g->create_achievement($_POST["new_ach"], $_POST["new_ach_amount"], $_POST["new_ach_period"], $_POST["new_ach_badge"]);
}
//save edited achievement info
if(isset($_POST["save_ach"]))
{
	$g->edit_achievement($_POST["save_ach"], $_POST["save_name"], $_POST["save_badge"], $_POST["save_amount"], $_POST["save_period"], $_POST["status"]);
}
//delete achievement
if(isset($_POST["del_ach"]) && $_POST["del_ach"] != "")
{
	$g->delete_achievement($_POST["del_ach"]);
}
/**************************************************
* USERS
**************************************************/
echo "<fieldset><legend>Users</legend>";
//create new
echo "<form method='post'><p>Enter username: <input type='text' name='new_user'/> <input type='submit' value='Create new user'/></p></form>";
//get user info
echo "<form method='post'><p>Information about user: <select name='info_user'>";
echo "<option value=''>---</option>";
$users = $g->get_users();
foreach($users as $val)
{
	echo "<option value='".$val["username"]."'>".$val["username"]."</option>";
}
echo "</select> <input type='submit' value='Get info'/></p></form>";
if(isset($_POST["info_user"]) && $_POST["info_user"] != "")
{
	$info = $g->get_user($_POST["info_user"]);
	echo "<fieldset><legend>Info about ".$info["username"]."</legend>";
	echo "<p>Username: ".$info["username"]."</p>";
	echo "<p>Experience: ".$info["experience"]."</p>";
	echo "<p>Level: ".$info["level"]."</p>";
	echo "<p>Achievements: <ul>";
	foreach($info["achievements"] as $val)
	{
		if($val["status"] == "completed")
		{
			echo "<li>".$val["achievement_name"]." - Badge: <img src='".$val["badge_src"]."' width='50px' border='0'/>. Earned : ".date("r", $val["time"]).". Status: Completed</li>";
		}
		else
		{
			echo "<li>".$val["achievement_name"]." - Badge: <img src='".$val["badge_src"]."' width='50px' border='0'/>. Earned : ".date("r", $val["time"]).". Status: ".$val["amount_got"]." out of ".$val["amount_needed"]."</li>";
		}
	}
	echo "</ul></p>";
	echo "</fieldset>";
}

//edit users
echo "<form method='post'><p>Edit users: <select name='edit_user'>";
echo "<option value=''>---</option>";
$users = $g->get_users();
foreach($users as $val)
{
	echo "<option value='".$val["username"]."'>".$val["username"]."</option>";
}
echo "</select> <input type='submit' value='Edit'/></p></form>";
//edit user
if(isset($_POST["edit_user"]) && $_POST["edit_user"] != "")
{
	$arr = $g->get_user($_POST["edit_user"]);
	echo "<fieldset><legend>Edit user ".$arr["username"]."</legend>";
	echo "<form method='post'>";
	echo "<p>Username: <input type='text' name='save_username' value='".$arr["username"]."'/></p>";
	echo "<p>Experience: <input type='text' name='save_experience' value='".$arr["experience"]."'/></p>";
	echo "<p>Level: <select name='save_level'>";
	$levels = $g->get_levels("experience_needed");
	foreach($levels as $val)
	{	if($val["ID"] == $arr["level"])
		{
			echo "<option value='".$val["ID"]."' selected>".$val["level_name"]."</option>";
		}
		else
		{	
			echo "<option value='".$val["ID"]."'>".$val["level_name"]."</option>";
		}
	}
	echo "</select></p>";
	echo "<input type='hidden' name='save_user' value='".$arr["ID"]."'/>";
	echo "<p><input type='submit' value='Save'/></p>";
	echo "</form>";
	echo "</fieldset>";
}
//delete users
echo "<form method='post'><p>Delete users: <select name='del_user'>";
echo "<option value=''>---</option>";
$users = $g->get_users();
foreach($users as $val)
{
	echo "<option value='".$val["username"]."'>".$val["username"]."</option>";
}
echo "</select> <input type='submit' value='Delete'/></p></form>";
echo "</fieldset>";

/**************************************************
* LEVELS
**************************************************/
echo "<fieldset><legend>Levels</legend>";
//create new
echo "<form method='post'><p>Enter level name: <input type='text' name='new_level'/> and experience needed for level <input type='text' name='new_level_exp'/> <input type='submit' value='Create new level'/></p></form>";
//edit level
echo "<form method='post'><p>Edit level: <select name='edit_level'>";
$levels = $g->get_levels("experience_needed");
foreach($levels as $val)
{
	echo "<option value='".$val["ID"]."'>".$val["level_name"]."</option>";
}
echo "</select> <input type='submit' value='Edit'/></p></form>";
//edit level
if(isset($_POST["edit_level"]) && $_POST["edit_level"] != "")
{
	$arr = $g->get_level($_POST["edit_level"]);
	echo "<fieldset><legend>Edit level ".$arr["level_name"]."</legend>";
	echo "<form method='post'>";
	echo "<p>Level name: <input type='text' name='save_name' value='".$arr["level_name"]."'/></p>";
	echo "<p>Experience: <input type='text' name='save_experience' value='".$arr["experience_needed"]."'/></p>";
	echo "<input type='hidden' name='save_level' value='".$arr["ID"]."'/>";
	echo "<p><input type='submit' value='Save'/></p>";
	echo "</form>";
	echo "</fieldset>";
}
//delete level
echo "<form method='post'><p>Delete level: <select name='del_level'>";
$levels = $g->get_levels("experience_needed");
foreach($levels as $val)
{
	echo "<option value='".$val["ID"]."'>".$val["level_name"]."</option>";
}
echo "</select> <input type='submit' value='Delete'/></p></form>";
echo "</fieldset>";

/**************************************************
* ACHIEVEMENTS
**************************************************/
echo "<fieldset><legend>Achievements</legend>";
//create new
echo "<form method='post'><p>Enter achievement name: <input type='text' name='new_ach'/><br/>Link to badge image: <input type='text' name='new_ach_badge'/><br/>Amount of actions for achievement: <input type='text' name='new_ach_amount'/><br/>Period of time in seconds between actions: <input type='text' name='new_ach_period'/><br/> <input type='submit' value='Create new achievement'/></p></form>";
//edit level
echo "<form method='post'><p>Edit achievement: <select name='edit_ach'>";
echo "<option value=''>---</option>";
$ach = $g->get_achievements();
foreach($ach as $val)
{
	echo "<option value='".$val["ID"]."'>".$val["achievement_name"]."</option>";
}
echo "</select> <input type='submit' value='Edit'/></p></form>";
//edit level
if(isset($_POST["edit_ach"]) && $_POST["edit_ach"] != "")
{
	$arr = $g->get_achievement($_POST["edit_ach"]);
	echo "<fieldset><legend>Edit achievement ".$arr["achievement_name"]."</legend>";
	echo "<form method='post'>";
	echo "<p>Achievement name: <input type='text' name='save_name' value='".$arr["achievement_name"]."'/></p>
	<p>Link to badge image: <input type='text' name='save_badge' value='".$arr["badge_src"]."'/></p>
	<p>Amount of actions for achievement: <input type='text' name='save_amount' value='".$arr["amount_needed"]."'/></p>
	<p>Period of time in seconds between actions: <input type='text' name='save_period' value='".$arr["time_period"]."'/></p>";
	echo "<select name='save_status'>";
	echo "<option value='active'>Active</option>";
	if($arr["status"] == "inactive")
	{
		echo "<option value='inactive' selected>Inactive</option>";
	}
	else
	{
		echo "<option value='inactive'>Inactive</option>";
	}
	echo "</select>";
	echo "<input type='hidden' name='save_ach' value='".$arr["ID"]."'/>";
	echo "<p><input type='submit' value='Save'/></p>";
	echo "</form>";
	echo "</fieldset>";
}
//delete users
echo "<form method='post'><p>Delete achievements: <select name='del_ach'>";
echo "<option value=''>---</option>";
$ach = $g->get_achievements();
foreach($ach as $val)
{
	echo "<option value='".$val["ID"]."'>".$val["achievement_name"]."</option>";
}
echo "</select> <input type='submit' value='Delete'/></p></form>";
echo "</fieldset>";

//errors
echo "<fieldset><legend>Errors</legend>";
$err = $g->get_errors();
foreach($err as $val)
{
	echo "<p>".$val."</p>";
}
echo "</fieldset>";
?>