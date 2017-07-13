<?php
/**
 * initialize.php
 *
 * Created by Pamela L. Gay
 * Use: StarStryder
 * Date: 7/10/17
 * Time: 12:49 PM
 *
 * This file
 * 0) Initializes things
 * 1) Gets all of a seed accounts followers
 *
 * On completion, run main.php
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



/***********************************************************************************************
 * 1) Get the seed user and all of the seed user's followers
 ***********************************************************************************************/

// GET INITIAL SEED USER -----------------------------------------------------------------------

// see documentation https://dev.twitter.com/rest/reference/get/users/show
    $url        = 'https://api.twitter.com/1.1/users/show.json';
    $getfield   = '?screen_name='.$seed;

    $obj = json_decode($twitter->setGetfield($getfield)->buildOauth($url, 'GET')->performRequest(), true);
    test_twshow($obj);

    $user_id        = $obj['id_str'];
    $username       = $obj['screen_name'];
    $name           = addslashes(substr($obj['name'], 0, 49));
    $descrip        = addslashes($obj['description']);
    $followers      = $obj['followers_count'];
    $friends        = $obj['friends_count'];
    $tweets         = $obj['statuses_count'];
    $created        = $obj['created_at'];

    if ($obj['verified']) $verify = 1; else $verify = 0;
    $status         = $obj['status'];
    $update         = $status['created_at'];

    $query = "INSERT INTO tweeps (tweep_id, tweep_username, tweep_name, description, followers, friends, tweets, verified, done, created_at, update_at)
                      VALUES ($user_id, '$username', '$name', '$descrip', $followers, $friends, $tweets, $verify, 1,'$created', '$update')";
    test_mysql_q($query, $conn);


// GET THE NETWORK OF THE SEED USER'S FOLLOWERS ------------------------------------------------

// NOTES: If a user has a lot of followers, they won't all get returned in a single query
// To get around this, a "Cursor" variable is used. As long as this isn't zero, there are more
// pages of content. See full documentation: https://dev.twitter.com/rest/reference/get/followers/list
// Returns info for 20 users
// Limited to 15 calls per 15 minutes

    echo "Getting $seed's followers ";

    $cursor         = 1;  // To start the while loop
    $call           = 1;
    $i 				= 0;
    while ($cursor) {
        if ($cursor == 1) $cursor = -1;
        echo ".";

        // This gets list of people who follow the seed user
        $url = "https://api.twitter.com/1.1/followers/list.json";
        $getfield = '?screen_name='.$seed.'&cursor='.$cursor;

        $objs = json_decode($twitter->setGetfield($getfield)->buildOauth($url, 'GET')->performRequest(), true);
        test_twlist($objs);

        // this returns a whole set of users
        $tweeps = $objs['users'];
        foreach($tweeps as $tweep) {
            $user_id    = $tweep['id_str'];
            $username   = $tweep['screen_name'];
            $name       = addslashes(substr($tweep['name'], 0, 49));
            $descrip    = addslashes($tweep['description']);
            $followers  = $tweep['followers_count'];
            $friends    = $tweep['friends_count'];
            $tweets     = $tweep['statuses_count'];
            $created    = $tweep['created_at'];

            if ($tweep['verified']) $verify = 1; else $verify = 0;
            $status     = $tweep['status'];
            $update     = $status['created_at'];

            $query = "INSERT INTO tweeps (tweep_id, tweep_username, tweep_name, description, followers, friends, tweets, verified, done, created_at, update_at)
                      VALUES ($user_id, '$username', '$name', '$descrip', $followers, $friends, $tweets, $verify, 0,'$created', '$update')";
            $i++;
            test_mysql_q($query, $conn);
        }

        // Move to the next page of responses
        $cursor = $objs['next_cursor'];

        // Don't run into the rate limit
        $call++;
        if ($call > 15) {
            $call = 1;
            echo "PAUSE at $cursor and user $username\n";
            sleep(15.1*60);
        }
    }