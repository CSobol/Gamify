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
class gamify
{
	private $con;
	private $pref = "gamify_";
	private $err = array();
	
	//create connection
	function __construct($host, $user, $pass, $db, $type = "mysql", $pref = "gamify_"){
		try{
			$this->con = new PDO($type.':host='.$host.';dbname='.$db, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		} 
		catch(PDOException $e){
			$this->err[] = 'Error connecting to MySQL!: '.$e->getMessage();
		}
		$this->con->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
		$this->pref = $pref;
		//timezone not set fix
		date_default_timezone_set(date_default_timezone_get());
	}
	//install sql tables
	public function install(){
		//achievements
		$sql = "CREATE TABLE IF NOT EXISTS `".($this->pref)."achievements` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`achievement_name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
		`badge_src` text COLLATE utf8_unicode_ci NOT NULL,
		`description` text COLLATE utf8_unicode_ci,
		`amount_needed` int(11) NOT NULL,
		`time_period` int(11) NOT NULL DEFAULT '0',
		`status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
		PRIMARY KEY (`ID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
		$this->con->exec($sql);
		
		//levels
		$sql = "CREATE TABLE IF NOT EXISTS `".($this->pref)."levels` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`level_name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
		`experience_needed` int(11) NOT NULL,
		PRIMARY KEY (`ID`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;";
		$this->con->exec($sql);
		
		try{
			//default zero level
			$sql = "INSERT INTO `".($this->pref)."levels` (`ID`, `level_name`, `experience_needed`) VALUES (1, '', 0);";
			$this->con->exec($sql);
		}
		catch(PDOException $e){
			$this->err[] = 'Error : '.$e->getMessage();
		}
		
		//user-achievement relation
		$sql = "CREATE TABLE IF NOT EXISTS `".($this->pref)."users_ach` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`userID` int(11) NOT NULL,
		`achID` int(11) NOT NULL,
		`amount` int(11) NOT NULL,
		`last_time` int(11) NOT NULL,
		`status` enum('active','completed') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
		PRIMARY KEY (`ID`),
		UNIQUE KEY `userID` (`userID`,`achID`),
		KEY `achID` (`achID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
		$this->con->exec($sql);
		
		//users and statistics
		$sql = "CREATE TABLE IF NOT EXISTS `".($this->pref)."user_stats` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`username` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
		`experience` int(11) NOT NULL DEFAULT '0',
		`level` int(11) NOT NULL DEFAULT '1',
		PRIMARY KEY (`ID`),
		UNIQUE KEY `username` (`username`),
		KEY `level` (`level`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
		$this->con->exec($sql);
		
		try{
			//user-achievement relation constraints
			$sql = "ALTER TABLE `".($this->pref)."users_ach`
			ADD CONSTRAINT `".($this->pref)."users_ach_ibfk_2` FOREIGN KEY (`achID`) REFERENCES `".($this->pref)."achievements` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
			ADD CONSTRAINT `".($this->pref)."users_ach_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `".($this->pref)."user_stats` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;";
			$this->con->exec($sql);
			
			//user level constraint
			$sql = "ALTER TABLE `".($this->pref)."user_stats`
			ADD CONSTRAINT `".($this->pref)."user_stats_ibfk_1` FOREIGN KEY (`level`) REFERENCES `".($this->pref)."levels` (`ID`) ON DELETE NO ACTION ON UPDATE CASCADE;";
			$this->con->exec($sql);
		}
		catch(PDOException $e){
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//output error messages
	public function debug(){
		$this->con->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	}
	
	//get internal error array
	public function get_errors(){
		return $this->err;
	}
	
	/**************************
	* USER MANIPULATIONS
	**************************/
	//create user
	public function create_user($username){
		try{
			$query = $this->con->prepare("SELECT ID FROM `".($this->pref)."user_stats` WHERE `username` = ?");
			$query->execute(array($username));
			//check if user doesn't exist
			if(!$query->fetch())
			{
				$query->closeCursor();
				$query = $this->con->prepare("INSERT INTO `".($this->pref)."user_stats` SET `username` = ?");
				$query->execute(array($username));
				$query->closeCursor();
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//get one user information
	public function get_user($username){
		try{
			$query = $this->con->prepare("SELECT a.ID, a.username, a.experience, b.level_name as level FROM `".($this->pref)."user_stats` as a,`".($this->pref)."levels` as b WHERE a.level = b.ID and `username` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($username));
			if($result = $query->fetch())
			{
				$query->closeCursor();
				$query = $this->con->prepare("SELECT b.achievement_name, b.badge_src, a.amount as amount_got, b.amount_needed, a.last_time as time, a.status FROM `".($this->pref)."users_ach` as a, `".($this->pref)."achievements` as b WHERE a.achID = b.ID and a.userID = ?");
				$query->setFetchMode(PDO::FETCH_ASSOC);
				$query->execute(array($result["ID"]));
				$result["achievements"] = $query->fetchAll();
				$query->closeCursor();
				return $result;
			}
			else
			{
				$this->err[] = 'There are no user with username '.$username;
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//get many user informations
	public function get_users($ord = "", $desc = false, $limit = 0){
		$add = "";
		if(in_array($ord, array("username", "experience")))
		{
			$add .= " ORDER BY ".$ord;
			if($desc)
			{
				$add .= " DESC";
			}
		}
		$limit = intval($limit);
		if($limit > 0)
		{
			$add .= " LIMIT ".$limit;
		}
		try{
			$query = $this->con->prepare("SELECT a.ID, a.username, a.experience, b.level_name as level FROM `".($this->pref)."user_stats` as a,`".($this->pref)."levels` as b WHERE a.level = b.ID".$add);
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			if($result = $query->fetchAll())
			{
				$query->closeCursor();
				return $result;
			}
			else
			{
				$this->err[] = 'There are no users';
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//edit users info
	public function edit_user($id, $username = "", $experience = "", $level = ""){
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."user_stats` WHERE `ID` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($id));
			if($user = $query->fetch())
			{
				$query->closeCursor();
				$username = ($username == "") ? $user["username"] : $username;
				$experience = ($experience == "") ? $user["experience"] : $experience;
				$level = ($level == "") ? $user["level"] : $level;
				$query = $this->con->prepare("UPDATE `".($this->pref)."user_stats` SET `username` = ?, `experience` = ?, `level` = ? WHERE `ID` = ?");
				$query->execute(array($username, $experience, $level, $id));
				$query->closeCursor();
			}
			else
			{
				$this->err[] = 'User with ID '.$id.' does not exist';
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//delete user
	public function delete_user($username){
		try{
			$query = $this->con->prepare("DELETE FROM `".($this->pref)."user_stats` WHERE `username` = ?");
			$query->execute(array($username));
			$query->closeCursor();
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	
	/**************************
	* LEVEL MANIPULATIONS
	**************************/
	//create new level
	public function create_level($name, $exp){
		try{
			$query = $this->con->prepare("INSERT INTO `".($this->pref)."levels` SET `level_name` = ?, `experience_needed` = ?");
			$query->execute(array($name, $exp));
			$query->closeCursor();
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//get level
	public function get_level($id){
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."levels` WHERE `ID` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($id));
			if($result = $query->fetch())
			{
				$query->closeCursor();
				return $result;
			}
			else
			{
				$this->err[] = 'There are no level with ID '.$id;
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//get levels
	public function get_levels($ord = "", $desc = false, $limit = 0){
		$add = "";
		if(in_array($ord, array("level_name", "experience_needed")))
		{
			$add .= " ORDER BY ".$ord;
			if($desc)
			{
				$add .= " DESC";
			}
		}
		$limit = intval($limit);
		if($limit > 0)
		{
			$add .= " LIMIT ".$limit;
		}
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."levels`".$add);
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			if($result = $query->fetchAll())
			{
				$query->closeCursor();
				return $result;
			}
			else
			{
				$this->err[] = 'There are no levels';
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//edit level
	public function edit_level($id, $name = "", $experience = ""){
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."levels` WHERE `ID` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($id));
			if($level = $query->fetch())
			{
				$query->closeCursor();
				$name = ($name == "") ? $level["level_name"] : $name;
				$experience = ($experience == "") ? $level["experience_needed"] : $experience;
				$query = $this->con->prepare("UPDATE `".($this->pref)."levels` SET `level_name` = ?, `experience_needed` = ? WHERE `ID` = ?");
				$query->execute(array($name, $experience, $id));
				$query->closeCursor();
			}
			else
			{
				$this->err[] = 'There are no level with ID '.$id;
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//delete level
	public function delete_level($id){
		if($id != 1)
		{
			try{
				$query = $this->con->prepare("DELETE FROM `".($this->pref)."user_stats` WHERE `ID` = ?");
				$query->execute(array($id));
				$query->closeCursor();
				$query = $this->con->prepare("UPDATE `".($this->pref)."user_stats` SET `level` = '1' WHERE `level` = ?");
				$query->execute(array($id));
				$query->closeCursor();
			}
			catch(PDOException $e) {
				$this->err[] = 'Error : '.$e->getMessage();
			}
		}
		else
		{
			$this->err[] = 'Can\'t delete default level. It can only be edited';
		}
	}
	
	/**************************
	* ACHIEVEMENT MANIPULATIONS
	**************************/
	//create new achievement
	public function create_achievement($name, $amount, $period = 0, $badge = "", $description = ""){
		try{
			$query = $this->con->prepare("INSERT INTO `".($this->pref)."achievements` SET `achievement_name` = ?, `amount_needed` = ?, `time_period` = ?, `badge_src` = ?, `description` = ?");
			$query->execute(array($name, $amount, $period, $badge, $description));
			$query->closeCursor();
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
			
	}
	//get achievement
	public function get_achievement($id){
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."achievements` WHERE `ID` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($id));
			if($result = $query->fetch())
			{
				$query->closeCursor();
				return $result;
			}
			else
			{
				$this->err[] = 'There are no achievements with ID '.$id;
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//get achievements
	public function get_achievements($ord = "", $desc = false, $limit = 0){
		$add = "";
		if(in_array($ord, array("achievement_name", "amount_needed")))
		{
			$add .= " ORDER BY ".$ord;
			if($desc)
			{
				$add .= " DESC";
			}
		}
		$limit = intval($limit);
		if($limit > 0)
		{
			$add .= " LIMIT ".$limit;
		}
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."achievements`".$add);
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute();
			if($result = $query->fetchAll())
			{
				$query->closeCursor();
				return $result;
			}
			else
			{
				$this->err[] = 'There are no achievements';
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//edit achievement
	public function edit_achievement($id, $name = "", $amount = "", $period = "", $badge = "", $description = "", $status = "active"){
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."achievements` WHERE `ID` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($id));
			if($ach = $query->fetch())
			{
				$query->closeCursor();
				$name = ($name == "") ? $ach["achievement_name"] : $name;
				$badge = ($badge == "") ? $ach["badge_src"] : $badge;
				$description = ($description == "") ? $ach["description"] : $description;
				$amount = ($amount == "") ? $ach["amount_needed"] : $amount;
				$period = ($period == "") ? $ach["time_period"] : $period;
				$query = $this->con->prepare("UPDATE `".($this->pref)."achievements` SET `achievement_name` = ?, `badge_src` = ?, `amount_needed` = ?, `time_period` = ?, `status` = ? WHERE `ID` = ?");
				$query->execute(array($name, $badge, $amount, $period, $status, $id));
				$query->closeCursor();
			}
			else
			{
				$this->err[] = 'There are no achievements with ID '.$id;
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//delete achievement
	public function delete_achievement($id){
		try{
			$query = $this->con->prepare("DELETE FROM `".($this->pref)."achievements` WHERE `ID` = ?");
			$query->execute(array($id));
			$query->closeCursor();
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
	}
	//disable achievement
	public function disable_achievement($id){
		$this->edit_achievement($id, "", "", "", "", "", "inactive");
	}
	//enable achievement
	public function enable_achievement($id){
		$this->edit_achievement($id, "", "", "", "", "", "active");
	}
	
	/**************************
	* USER INTERACTION
	**************************/
	//add experience to user
	public function add_experience($username, $exp){
		try{
			//get info
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."user_stats` WHERE `username` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($username));
			if($row = $query->fetch())
			{
				$query->closeCursor();
				$exp += $row["experience"];
				//check if new level
				$query = $this->con->prepare("SELECT * FROM `".($this->pref)."levels` WHERE `experience_needed` = (SELECT max(`experience_needed`) FROM `".($this->pref)."levels` WHERE `experience_needed` <= ?)");
				$query->execute(array($exp));
				$query->setFetchMode(PDO::FETCH_ASSOC);
				$level = $query->fetch();
				if($level && $level["ID"] != $row["level"])
				{
					$query->closeCursor();
					//update experience and level info
					$query = $this->con->prepare("UPDATE `".($this->pref)."user_stats` SET `experience` = ?, `level` = ? WHERE `ID` = ?");
					$query->execute(array($exp, $level["ID"], $row["ID"]));
					$query->closeCursor();
					return $level;
				}
				else
				{
					$query->closeCursor();
					//update experience info
					$query = $this->con->prepare("UPDATE `".($this->pref)."user_stats` SET `experience` = ? WHERE `ID` = ?");
					$query->execute(array($exp, $row["ID"]));
					$query->closeCursor();
				}
			}
			else
			{	
				//user doesn't exist, create user and add experience
				$this->create_user($username);
				$this->add_experience($username, $exp);
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
		return false;
	}
	//times of completed actions for achievements
	public function action($username, $achievement, $amount = 1){
		try{
			$query = $this->con->prepare("SELECT * FROM `".($this->pref)."user_stats` WHERE `username` = ?");
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->execute(array($username));
			if($user = $query->fetch())
			{
				$query->closeCursor();
				$query = $this->con->prepare("SELECT * FROM `".($this->pref)."achievements` WHERE `ID` = ?");
				$query->setFetchMode(PDO::FETCH_ASSOC);
				$query->execute(array($achievement));
				if($ach = $query->fetch())
				{
					//checking if achievement is enabled
					if($ach["status"] == "active")
					{
						$now = time();
						$complete = false;
						$query->closeCursor();
						$query = $this->con->prepare("SELECT * FROM `".($this->pref)."users_ach` WHERE `userID` = ? and `achID` = ?");
						$query->setFetchMode(PDO::FETCH_ASSOC);
						$query->execute(array($user["ID"], $ach["ID"]));
						if($rel = $query->fetch())
						{
							$query->closeCursor();
							//checking if achievement is not completed yet
							if($rel["status"] == "active")
							{
								$amount += $rel["amount"];
								//checking if needed period of time is passed
								if($now >= $rel["last_time"] + $ach["time_period"])
								{
									//checking if no we have completed an achievement
									if($amount >= $ach["amount_needed"])
									{
										//complete achievement
										$query = $this->con->prepare("UPDATE `".($this->pref)."users_ach` SET `amount` = ?, `status` = 'completed', `last_time` = ? WHERE ID = ?");
										$complete = true;
									}
									else
									{
										//update existing relation
										$query = $this->con->prepare("UPDATE `".($this->pref)."users_ach` SET `amount` = ?, `last_time` = ? WHERE `ID` = ?");
									}
									$query->execute(array($amount, $now, $rel["ID"]));
									$query->closeCursor();
									if($complete)
									{
										return $ach;
									}
								}
							}
						}
						else
						{
							$query->closeCursor();
							$status = "active";
							if($amount >= $ach["amount_needed"])
							{
								$status = "completed";
								$complete = true;
							}
							//create relation
							$query = $this->con->prepare("INSERT INTO `".($this->pref)."users_ach` SET `userID` = ?, `achID` = ?, `amount` = ?, `last_time` = ?, `status` = ?");
							$query->execute(array($user["ID"], $ach["ID"], $amount, $now, $status));
							$query->closeCursor();
							if($complete)
							{
								return $ach;
							}
						}
					}
				}
				else
				{	
					$this->err[] = "Achievement with ID ".$achievement." does not exist";
				}
			}
			else
			{	
				//user doesn't exist, create user and perform action
				$this->create_user($username);
				$this->action($username, $achievement, $amount);
			}
		}
		catch(PDOException $e) {
			$this->err[] = 'Error : '.$e->getMessage();
		}
		return false;
	}
	
	//free resources
	function __destruct(){
		$this->con = NULL;
	}
}
?>