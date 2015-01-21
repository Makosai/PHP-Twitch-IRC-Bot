/*
REQUIREMENTS: PHP and SQLite3 with PHP.

CREDITS: You don't have to give credits. But, if you feel like doing so, my twitch channel is http://twitch.tv/quaintshanty. Credits will be greatly appreciated.

Instructions: This is an old bot that I don't use anymore. So, you'll have to work out some kinks yourself.
First, set your $chan to your desired #username_of_channel.

Leave the $server.
Leave the $port.

Second, change the $nick to the nick of your bot.
Third, sign in to http://tmi.twitchapps.com with your bot and get your oauth:password and paste it as $pass.

Upon running this the first time, you will need to do the following OR you can skip this and try the final paragraph in the instructions:
Find and replace the value of "$VC->storedVariables = json_decode($VC->db->querySingle("SELECT variables FROM stored_variables"), true);" or something close to it with the default storedVariables below (NOTE: BE SURE TO WRAP THE FOLLOWING BELOW IN json_decode(default value here, true)!!!!!!!! WHAT IS BELOW IS A JSON ARRAY. YOU WANT TO DECODE IT TO SET UP YOUR BOT.):

adminUsers is a json list of users who can perform admin commands with the bot.
ignoredUsers are users who the bot should ignore. I'm not sure, but I think it ignores itself. Once again, work out some of the kinks.
welcomeToggle, if set to true, the bot will say hello to whoever logs in.
welcomeMessage is the message the bot says to people who log in. Their name comes directly after it so include a space in the message if you want to actually space it.
goodbye... (this is self-explanatory. It's just like welcome.)
setCommandLimitTime is the time you want the bot to way between commandMaxLimit. (i.e. I run !time !time !time !time when commandMaxLimit is 4, the max command limit of 4 has been reached. After 60 seconds has passed, it will be reset. So, only 4 commands in 60 seconds can be ran.)
setPointsTime is how often points should be sent out to all users in the stream.
pointsModifier is how many points to get every user in the stream for each time the setPointsTime is reached.

What your storedVariables should look like:

$VC->storedVariables = json_decode("{\"adminUsers\" :	[\"quaintshanty\"], \"ignoredUsers\" : [\"moobot\", \"nightbot\", \"shantypantsbot\"], \"welcomeToggle\" : false, \"welcomeMessage\" : \"Hello, \", \"goodbyeToggle\" : false, \"goodbyeMessage\" : \"Bye, \", \"setCommandLimitTime\" : 60, \"commandMaxLimit\" : 4,	\"setPointsTime\" : 900, \"pointsModifier\" : 2}", true);

If you know anything about programming, you know \" is an escape character. Once again, I haven't used this bot in forever. I didn't design it for others to use. But, I tried my best to explain it. So, double check that all the quotations are escaped EXCEPT for the ones that wrap the json string.

After you have replaced storedVariables, ctrl+z to undo so you have "$VC->storedVariables = json_decode($VC->db->querySingle("SELECT variables FROM stored_variables"), true);" again. This will now load your configuration from the DB that is created.

You may also want to check that the database actually saved the configurations before reverting. If it hasn't, try creating the table "stored_variables" and a column called "variables" and set it to equal the defaultVariables.

Goodluck!
*/

<?php

class VariableClass {

  #region SETTINGS
	public $chan = "#quaintshanty";
	public $server = "irc.twitch.tv";
	public $port = 6667;
	public $nick = "quaintbot";
	public $pass = ""; //http://tmi.twitchapps.com <-- Go there for your oauth key.
	#end

	public $socket;

	#region VARIABLES

	//Database variables
	public $db;
	public $storedVariables;

	/*Default storedVariables

	{"adminUsers" :	["quaintshanty"], "ignoredUsers" : ["moobot", "nightbot", "shantypantsbot"], "welcomeToggle" : false, "welcomeMessage" : "Hello, ", "goodbyeToggle" : false, "goodbyeMessage" : "Bye, ", "setCommandLimitTime" : 60, "commandMaxLimit" : 4,	"setPointsTime" : 900, "pointsModifier" : 2}

	*/

	//User lists
	public $users = array();
	public $adminUsers;
	public $modUsers;
	public $ignoredUsers;

	//Command variables
	public $welcomeToggle;
	public $welcomeMessage;
	public $goodbyeToggle;
	public $goodbyeMessage;

	public $setCommandLimitTime;
	public $commandLimitTimer;
	public $commandLimit = 0;
	public $commandMaxLimit;

	//Points system variables
	public $setPointsTime;
	public $pointsTimer;
	public $pointsModifier;
	
	//Raffle
	public $raffleEntries = array();
	#end
}


// Prevent PHP from stopping the script after 30 sec
set_time_limit(0);

// Set the timezone
date_default_timezone_set('America/New_York'); //ENTER YOUR TIMEZONE HERE. FIND AVAILABLE TIMEZONES HERE: http://www.php.net/manual/en/timezones.php

#region VC Vars
$VC = new VariableClass();

$VC->socket = fsockopen($VC->server, $VC->port);

fputs($VC->socket,"PASS $VC->pass\n");
fputs($VC->socket,"NICK $VC->nick\n");
fputs($VC->socket,"JOIN " . $VC->chan . "\n");

$VC->db = new SQLite3('IRCBotDB');

$VC->storedVariables = json_decode($VC->db->querySingle("SELECT variables FROM stored_variables"), true);

$VC->adminUsers = $VC->storedVariables['adminUsers'];
$VC->ignoredUsers = $VC->storedVariables['ignoredUsers'];
$VC->welcomeToggle = $VC->storedVariables['welcomeToggle'];
$VC->welcomeMessage = $VC->storedVariables['welcomeMessage'];
$VC->goodbyeToggle = $VC->storedVariables['goodbyeToggle'];
$VC->goodbyeMessage = $VC->storedVariables['goodbyeMessage'];
$VC->setCommandLimitTime = $VC->storedVariables['setCommandLimitTime'];
$VC->commandLimitTimer = $VC->setCommandLimitTime;
$VC->commandMaxLimit = $VC->storedVariables['commandMaxLimit'];
$VC->setPointsTime = $VC->storedVariables['setPointsTime'];
$VC->pointsTimer = $VC->setPointsTime;
$VC->pointsModifier = $VC->storedVariables['pointsModifier'];
#end


function StripTrim($strip, $trim){
	$strippedString = (string)stripcslashes(trim($strip, $trim));
	$strippedString = preg_replace('~[.[:cntrl:][:space:]]~', '', $strippedString);
	return $strippedString;
}

function BasicChat($socket, $chan, $text){
	fputs($socket, "PRIVMSG ". $chan . " :" . $text . " \n");
}

function UserCommands($users, $sender, $socket, $chan, $message, $rawcmd, $args){
	//Verify that there are arguments.
	if(!is_null($args)){
		switch($rawcmd[1]) {
			case "!sayit" :
				fputs($socket, "PRIVMSG " . $chan . " :" . $args . "\n");
				break;

			case "!md5" :
				fputs($socket, "PRIVMSG " . $chan . " :MD5 " . md5($args) . "\n");
				break;
			
			case "!quote" :
				if(count($args)>1){
					fputs($socket, "PRIVMSG " . $chan . " :Quote system coming soon.\n");
				}
				else {
					
				}				
				break;
		}
	}
	else {
		switch($rawcmd[1]) {
			case "!points" :
				for($i=0; $i < count($users); $i++){
					if($sender == $users[$i]['name']){
						fputs($socket, "PRIVMSG " . $chan . " :" . $sender . ", you have " . $users[$i]['points'] . " points.\n");
						break;
					}
				}
				break;
				
			case "?points" : 
				fputs($socket, "PRIVMSG " . $chan . " :With points, you can use them to play games (coming soon) and enter in giveaways (coming soon), or become chosen for special events with the broadcaster. The more points, the better the rewards!\n");
				break;

			case "!timeHere" :
				for($i=0; $i < count($users); $i++){
					if($sender == $users[$i]['name']){
						fputs($socket, "PRIVMSG " . $chan . " :" . $sender .  ", you have been here for " . secondsToTime(round((microtime(true) - $users[$i]['time_joined']))) . ".\n");
						break;
					}
				}
		}
	}
}

function secondsToTime($seconds) {
    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
}

function SaveVariable($VC, $db){
	$storedVariablesJSONed = json_encode($VC->storedVariables);
	$db->query("UPDATE stored_variables SET variables = '{$storedVariablesJSONed}'");
}

function AdminCommands($message, $rawcmd, $args, $VC){
	//Verify that there are arguments.
	if(!is_null($args)){
		switch($rawcmd[1]) {
			case "!mod" :
				if(!in_array(StripTrim($args, ":"), $VC->adminUsers)) {
					$VC->adminUsers[] = StripTrim($args, ":");
					$VC->storedVariables['adminUsers'][] = StripTrim($args, ":");;
					SaveVariable($VC, $VC->db);
				}
		}
	}
	else {
		switch($rawcmd[1]) {
			case "!users" :
				var_dump($VC->users);
				break;
		}
	}
}

function AddPointsToAll($users, $pointsModifier, $db) {
	for($i = 0; $i < count($users); $i++){
		$users[$i]['points'] += $pointsModifier;
		$db->query("UPDATE users SET points = '{$users[$i]['points']}' WHERE name = '{$users[$i]['name']}'");
	}
	
	return $users;
}

// Set timout to 1 second
if (!stream_set_timeout($VC->socket, 1)) die("Could not set timeout");

while(1) {

	//points timer
	if($VC->pointsTimer > 0) {
		$VC->pointsTimer--;
	}
	else {
		$VC->pointsTimer = $VC->setPointsTime;
		$VC->users = AddPointsToAll($VC->users, $VC->pointsModifier, $VC->db);
	}
	
	//commands limit
	if($VC->commandLimitTimer > 0) {
		$VC->commandLimitTimer--;
	}
	else {
		$VC->commandLimitTimer = $VC->setCommandLimitTime;
		$VC->commandLimit = 0;
	}
	
	while($data = fgets($VC->socket)) {
	    flush();

		//Separate the incoming data by spaces and add them to the the message variable as a list.
		$message = explode(' ', $data);

		//If the server sends us a ping, pong those suckers back!
		if($message[0] == "PING"){
        	fputs($VC->socket, "PONG " . $message[1] . "\n");
	    }
		else {
			echo $data;
		}

		if($message[1] == "353"){
			//Adds all current users to the user list.
			for($i = 5; $i < count($message); $i++){
				$strippedUser = StripTrim($message[$i], ":"); //Trim is needed for the first user since it starts with :, sadly.

				if(!in_array($strippedUser, $VC->ignoredUsers) && !in_array($strippedUser, $VC->users)){
					$db_name = $VC->db->querySingle("SELECT name FROM users WHERE name = '" . $strippedUser . "'", false);
					
					if($db_name) {
						$db_points = $VC->db->querySingle("SELECT points FROM users WHERE name = '" . $strippedUser . "'", false);
						$VC->users[] = array(
								'name' => $db_name,
								'points' => $db_points,
								'time_joined' => microtime(true)
						); //Add them to the users list.
					} else {
						$VC->users[] = array(
								'name' => $strippedUser,
								'points' => 0,
								'time_joined' => microtime(true)
						); //Add them to the users list without loading from the DB.

						$VC->db->query("INSERT INTO users (name, points) VALUES ('" . $strippedUser . "', 0)"); //Add user to DB
					}
				}
			}
		}
		elseif($message[1] == "JOIN"){
			$temp = explode('!', (string)$message[0]);
			$joinedUser = StripTrim($temp[0], ":");
			if(!in_array($joinedUser, $VC->ignoredUsers) && !in_array($joinedUser, $VC->users)){
				$db_name = $VC->db->querySingle("SELECT name FROM users WHERE name = '" . $joinedUser . "'", false);
				
				if($db_name) {
					$db_points = $VC->db->querySingle("SELECT points FROM users WHERE name = '" . $joinedUser . "'", false);
					$VC->users[] = array(
							'name' => $db_name,
							'points' => $db_points,
								'time_joined' => microtime(true)
					); //Add them to the users list.
				} else {
					$VC->users[] = array(
							'name' => $joinedUser,
							'points' => 0,
							'time_joined' => microtime(true)
					); //Add them to the users list without loading from the DB.

					$VC->db->query("INSERT INTO users (name, points) VALUES ('" . $joinedUser . "', 0)"); //Add user to DB
				}

				if($VC->welcomeToggle){
					BasicChat($VC->socket, $VC->chan, $VC->welcomeMessage . $joinedUser . "!");
				}
			}
		}
		elseif($message[1] == "PART"){
			$temp = explode('!', (string)$message[0]);
			$partedUser = StripTrim($temp[0], ":");
			
			for($i=0; $i < count($VC->users); $i++){
				if($partedUser == $VC->users[$i]['name']){
					unset($VC->users[$i]['name']); //Remove them from the users list.
					$VC->users = array_values($VC->users);
					unset($VC->users[$i]['points']);
					$VC->users = array_values($VC->users);
					unset($VC->users[$i]['time_joined']);
					$VC->users = array_values($VC->users);
					unset($VC->users[$i]);
					$VC->users = array_values($VC->users);
					break;
				}
			}

			if($VC->goodbyeToggle){
				if(!in_array($partedUser, $VC->ignoredUsers)){
					BasicChat($VC->socket, $VC->chan, $VC->goodbyeMessage . $partedUser . "!");
				}
			}
		}
		elseif($message[1] == "MODE"){
			// Add mods
		}
		elseif($message[1] == "PRIVMSG"){
			echo "Entered...";
			if($VC->users!=NULL && count($VC->users)>0) {
				$temp = explode('!', (string)$message[0]);
				$sender = StripTrim($temp[0], ":");
				
				$rawcmd = explode(':', $message[3]); //Get the raw command from the message.
				
				//Get all arguments after the raw command.
				$args = NULL;
				if(count($message) > 4){
					for($i = 4; $i < count($message); $i++){
						$args .= $message[$i] . ' ';
					}
				}
				
				$rawcmd = preg_replace('~[.[:cntrl:][:space:]]~', '', $rawcmd);
				
				if(substr($rawcmd[1], 0, 1) == "!" && $VC->commandLimit < ($VC->commandMaxLimit + 1))
					$VC->commandLimit++;
				echo "Commanding...";
				echo $rawcmd[1];
				if($rawcmd[1] == "!raffle") {
					echo "Raffling...";
					for($i=0; $i < count($users); $i++){
						if($sender == $VC->users[$i]['name']){
							echo $i . "PLACE...";
							if($VC->users[$i]['points'] >= 5){
								echo "Success";
								if(!in_array($VC->users[$i]['name'], $VC->raffle))
								{
									$VC->users[$i]['points'] -= 5;
									fputs($$VC->socket, "PRIVMSG " . $VC->chan . " :You are entered in to the raffle!\n");
								}
								else
								{
									fputs($$VC->socket, "PRIVMSG " . $VC->chan . " :You are already entered!\n");
								}
							}
							else {
								echo "Fail";
								fputs($VC->socket, "PRIVMSG " . $VC->chan . " :You need 5 or more points to enter the raffle.\n");
							}
							break;
						}
					}
				}
				else
				{
					//Make it so users can't spam commands.
					if($VC->commandLimit <= $VC->commandMaxLimit)
						UserCommands($VC->users, $sender, $VC->socket, $VC->chan, $message, $rawcmd, $args);
					
					//replace this will array_search by assigning an admin variable in the future.
					if(in_array($sender, $VC->adminUsers)){
						AdminCommands($message, $rawcmd, $args, $VC);
					}
				}
			}
		}
	}
	
	if (!feof($VC->socket)) {
		continue;
	}
	
	sleep(1);
}
?>
