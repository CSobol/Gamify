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
//create class instance with database connection
$g = new gamify("localhost", "root", "password", "gamify");

//output pdo errors
$g->debug();

//create sql tables (only for the first time)
$g->install();

//create new user
//providing username
$g->create_user("ar2rsawseen");

//create new level
//providing level name and experience needed
$g->create_level("First level", 100);

//create new achievement for click every 24 hours
//providing achievement name, actions needed, 
//time period in seconds between actions, 
//optional achievement badge and description
$g->create_achievement("Clicker", 100, 60*60*24, "./cbadge.png", "Do 100 clicks");

echo "<pre>";
//add experience to created user and output new gained level
//by providing username and amount of experience
print_r($g->add_experience("ar2rsawseen", 100));

//automatically create user if it doesn't exist and add experience
//by providing username and amount of experience
print_r($g->add_experience("ar2rs", 50));

//output user information and errors if any
//output information about users
print_r($g->get_user("ar2rsawseen"));
print_r($g->get_user("ar2rs"));
//output errors
print_r($g->get_errors());
echo "</pre>";
?>