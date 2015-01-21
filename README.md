<b>REQUIREMENTS</b>
PHP and SQLite3 with PHP.
 
<b>CREDITS</b>
You don't have to give credits. But, if you feel like doing so, my twitch channel is http://twitch.tv/quaintshanty. Credits will be greatly appreciated.
 
<b>Instructions</b>
This is an old bot that I don't use anymore. So, you'll have to work out some kinks yourself.

First, set your $chan to your desired #username_of_channel.
 
Leave the $server.
Leave the $port.
 
Second, change the $nick to the nick of your bot.
Third, sign in to http://tmi.twitchapps.com with your bot and get your oauth:password and paste it as $pass.
 
 <hr>
 
<b>Upon running this the first time, you will need to do the following OR you can skip this and try the final paragraph in the instructions:</b>
Find and replace the value of "$VC->storedVariables = json_decode($VC->db->querySingle("SELECT variables FROM stored_variables"), true);" or something close to it with the default storedVariables below (NOTE: BE SURE TO WRAP THE FOLLOWING BELOW IN json_decode(default value here, true)!!!!!!!! WHAT IS BELOW IS A JSON ARRAY. YOU WANT TO DECODE IT TO SET UP YOUR BOT.):
 
 
 
adminUsers is a json list of users who can perform admin commands with the bot.

ignoredUsers are users who the bot should ignore. I'm not sure, but I think it ignores itself. Once again, work out some of the kinks.

welcomeToggle, if set to true, the bot will say hello to whoever logs in.

welcomeMessage is the message the bot says to people who log in. Their name comes directly after it so include a space in the message if you want to actually space it.

goodbye... (this is self-explanatory. It's just like welcome.)

setCommandLimitTime is the time you want the bot to way between commandMaxLimit. (i.e. I run !time !time !time !time when commandMaxLimit is 4, the max command limit of 4 has been reached. After 60 seconds has passed, it will be reset. So, only 4 commands in 60 seconds can be ran.)

setPointsTime is how often points should be sent out to all users in the stream.

pointsModifier is how many points to get every user in the stream for each time the setPointsTime is reached.

<hr>

What your storedVariables should look like:

$VC->storedVariables = json_decode("{\"adminUsers\" :   [\"quaintshanty\"], \"ignoredUsers\" : [\"moobot\", \"nightbot\", \"shantypantsbot\"], \"welcomeToggle\" : false, \"welcomeMessage\" : \"Hello, \", \"goodbyeToggle\" : false, \"goodbyeMessage\" : \"Bye, \", \"setCommandLimitTime\" : 60, \"commandMaxLimit\" : 4,   \"setPointsTime\" : 900, \"pointsModifier\" : 2}", true);
 
If you know anything about programming, you know \" is an escape character. Once again, I haven't used this bot in forever. I didn't design it for others to use. But, I tried my best to explain it. So, double check that all the quotations are escaped EXCEPT for the ones that wrap the json string.
 
After you have replaced storedVariables, ctrl+z to undo so you have "$VC->storedVariables = json_decode($VC->db->querySingle("SELECT variables FROM stored_variables"), true);" again. This will now load your configuration from the DB that is created.
 
You may also want to check that the database actually saved the configurations before reverting. If it hasn't, try creating the table "stored_variables" and a column called "variables" and set it to equal the defaultVariables.
 
Goodluck!
