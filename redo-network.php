<?php
/**
 * redo-network.php
 *
 * Created by Pamela L. Gay
 * Use: StarStryder
 * Date: 7/10/17
 * Time: 12:49 PM
 *
 * This file repeats these steps from main.php with a new depth value set below instead of in the config
 * 4) Get the details for hubs followed by a large fraction of the people following the seed
 * 5) Let's map the network by finding out how connected each of the hubs are to one another
 *
 */

/***********************************************************************************************
 * 0) Iniitalize everything
 ***********************************************************************************************/

// INCLUDES ------------------------------------------------------------------------------------

// Tests to make sure code is not doing evil
require_once('tests.php');

// Library for connecting to Twitter API
require_once('TwitterAPIExchange.php');

// Setup all need tokens and values
require_once('config.php'); // COPY config_SAMPLE.php to config.php and add in your values

echo "starting...\n";


// Initial variables ---------------------------------------------------------------------------

$ids_array = array(); // we'll store all user ids in this to reduce DB calls

$depth = 0.1;

$cutoff = 5000;

/***********************************************************************************************
 * 4) Get the details for hubs followed by a large fraction of the people following the seed
 ***********************************************************************************************/

// NOTES: Calculate how many people need to follow someone for them to be a hub
// This actually only returns how many people are non-protected accounts, which is
// fine since we know nothing about protected accounts.

// hubSize = Total Follower Number * Depth
    $query      = "select count(distinct tweep_id) as N FROM connections";
    $result     = test_mysql_q($query, $conn);
    $followers  = mysqli_fetch_assoc($result);

    foreach ($followers as $total);

    $hubSize = $total * $depth;

// Find all the hubs
    $call = 1;
    $curr = 0;
    $query = "SELECT * FROM network WHERE count >= $hubSize ORDER BY count DESC";
    $hubs = test_mysql_q($query, $conn);

    echo "getting hub details. \n";

// Get all their details
    foreach ($hubs as $hub) {
        // see documentation https://dev.twitter.com/rest/reference/get/users/show
        $url        = 'https://api.twitter.com/1.1/users/show.json';
        $getfield   = '?user_id='.$hub['tweep_id'];

        $obj = json_decode($twitter->setGetfield($getfield)->buildOauth($url, 'GET')->performRequest(), true);
        test_twshow($obj);

        $user_id        = $obj['id_str'];
        $user_name      = $obj['screen_name'];
        $name           = addslashes($obj['name']);
        $descrip        = addslashes($obj['description']);
        $followers      = $obj['followers_count'];
        $friends        = $obj['friends_count'];
        $tweets         = $obj['statuses_count'];
        $created        = $obj['created_at'];

        if ($obj['verified']) $verify = 1; else $verify = 0;
        $status         = $obj['status'];
        $update         = $status['created_at'];


        $query = "UPDATE network SET tweep_id = '$user_id', tweep_username = '$user_name', tweep_name='$name', description='$descrip',
                  followers=$followers, friends=$friends, tweets=$tweets, done=1, verified=$verify, created_at='$created', update_at='$update'
                  WHERE tweep_id = '".$hub['tweep_id']."'";
        test_mysql_q($query, $conn);

        $call++; $curr++;

        if ($call > 900) {
            $call = 1;
            echo "Pause before starting $curr";
            sleep(15.1*60);
        }

    }

/***********************************************************************************************
 * 5) Let's map the network by finding out how connected each of the hubs are to one another
 *
 * This is a multi-step process
 ***********************************************************************************************/

// 1. Put all the hubs into an array

$hubs_array = array();
$i = 0;
foreach ($hubs as $hub) {
    $hubs_array[$i] = array("tweep_id"=>$hub['tweep_id'], "tweep_username"=>$hub['tweep_username']);
    $i++;
}

// 2. Create a table of all possible combinations
$max = $i-1; // because 0 indexed

for($i = 0; $i<$max; $i++) {
    for($j=$max; $j != $i; $j--){
        $query = "INSERT INTO combos (HubA_id, HubA_name, HubB_id, HubB_name) values
                  ('".$hubs_array[$i]['tweep_id']."', '".$hubs_array[$i]['tweep_username']."', '".$hubs_array[$j]['tweep_id']."', '".$hubs_array[$j]['tweep_username']."' )";
        $result     = test_mysql_q($query, $conn);
    }
}

// 3. Put all combinations into a variable to step through
$query      = "SELECT * FROM combos";
$combos     = test_mysql_q($query, $conn);


// 4. For each follower, using a cutoff from aboe
$query      = "SELECT * FROM tweeps WHERE friends < $cutoff";
$tweeps     = test_mysql_q($query, $conn);
echo "checking ";

foreach ($tweeps as $tweep) {
    echo " " . $tweep['id'] . " ";

    // a. put the list of accounts they follow - their friends - in an array
    $chk_array  = array();
    $chk_id     = $tweep['tweep_id'];
    $query      = "SELECT * FROM connections WHERE tweep_id = '$chk_id'";
    $friends  = test_mysql_q($query, $conn);
    foreach ($friends as $friend) {
        array_push( $chk_array, $friend['friend_id'] );
    }

    // b. check if the combos are in the arrray, if they are, increment that count
    foreach ($combos as $combo) {
        if(in_array($combo['HubA_id'], $chk_array) && in_array($combo['HubB_id'], $chk_array)) {
            $query      = "UPDATE combos SET count = count + 1 WHERE id =" . $combo['id'];
            test_mysql_q($query, $conn);
        }
    }

    // c. release that array
    unset($chk_array);
    $chk_array = array();
}

// That *should* be it



