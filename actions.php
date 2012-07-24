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
//experience
echo "<fieldset><legend>Experience</legend>";
echo "<form method='post'><p>Username: <select name='exp_user'>";
$users = $g->get_users();
foreach($users as $val)
{
	echo "<option value='".$val["username"]."'>".$val["username"]."</option>";
}
echo "</select> amount of experience: <select name='exp_amount'>";
echo "<option value='1'>1</option>";
echo "<option value='10'>10</option>";
echo "<option value='20'>20</option>";
echo "<option value='50'>50</option>";
echo "<option value='100'>100</option>";
echo "</select> <input type='submit' value='Get Experience'/></p></form>";
if(isset($_POST["exp_user"]))
{
	$level = $g->add_experience($_POST["exp_user"], $_POST["exp_amount"]);
	$info = $g->get_user($_POST["exp_user"]);
	echo "<fieldset><legend>Info about ".$info["username"]."</legend>";
	echo "<p>Username: ".$info["username"]."</p>";
	echo "<p>Experience: ".$info["experience"]."</p>";
	echo "<p>Level: ".$info["level"]."</p>";
	echo "<p>Achievements: <ul>";
	foreach($info["achievements"] as $val)
	{
		if($val["status"] == "completed")
		{
			echo "<li>".$val["achievement_name"]." - Badge: <img src='".$val["badge_src"]."' width='50px' border='0'/>. Earned : ".date("r", $val["time"])."</li>";
		}
	}
	echo "</ul></p>";
	echo "</fieldset>";
	if(is_array($level))
	{
		echo "<fieldset><legend>Level up</legend>";
		echo "<h1>New level achieved: ".$level["level_name"]."</h1>";
		echo "</fieldset>";
	}
}
echo "</fieldset>";
//achievements
echo "<fieldset><legend>Achievements</legend>";
echo "<form method='post'><p>Username: <select name='ach_user'>";
$users = $g->get_users();
foreach($users as $val)
{
	echo "<option value='".$val["username"]."'>".$val["username"]."</option>";
}
echo "</select>";
echo " achievement: <select name='ach_id'>";
$ach = $g->get_achievements();
foreach($ach as $val)
{
	echo "<option value='".$val["ID"]."'>".$val["achievement_name"]."</option>";
}
echo "</select>";
echo " amount of actions: <select name='ach_amount'>";
echo "<option value='1'>1</option>";
echo "<option value='10'>10</option>";
echo "<option value='20'>20</option>";
echo "<option value='50'>50</option>";
echo "<option value='100'>100</option>";
echo "</select> <input type='submit' value='Perform action'/></p></form>";
if(isset($_POST["ach_user"]))
{
	$ach = $g->action($_POST["ach_user"], $_POST["ach_id"], $_POST["ach_amount"]);
	$info = $g->get_user($_POST["ach_user"]);
	echo "<fieldset><legend>Info about ".$info["username"]."</legend>";
	echo "<p>Username: ".$info["username"]."</p>";
	echo "<p>Experience: ".$info["experience"]."</p>";
	echo "<p>Level: ".$info["level"]."</p>";
	echo "<p>Achievements: <ul>";
	foreach($info["achievements"] as $val)
	{
		if($val["status"] == "completed")
		{
			echo "<li>".$val["achievement_name"]." - Badge: <img src='".$val["badge_src"]."' width='50px' border='0'/>. Earned : ".date("r", $val["time"])."</li>";
		}
	}
	echo "</ul></p>";
	echo "</fieldset>";
	if(is_array($ach))
	{
		echo "<fieldset><legend>Achievement unlocked</legend>";
		echo "<h1>New achievement unlocked: ".$ach["achievement_name"]."</h1>";
		echo "<p><img src='".$ach["badge_src"]."' border='0'/></p>";
		echo "</fieldset>";
	}
}
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