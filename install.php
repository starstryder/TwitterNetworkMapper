<?php
/**
 * install.php
 *
 * Created by PhpStorm.
 * User: pamela
 * Date: 7/10/17
 * Time: 1:47 PM
 */

// INCLUDES ------------------------------------------------------------------------------------

// Tests to make sure code is not doing evil
require_once('tests.php');

// Library for connecting to Twitter API
require_once('TwitterAPIExchange.php');

// Make needed connetions to DB
require_once('config.php');

// Create database as needed
$query = "CREATE DATABASE IF NOT EXISTS $dbname";
$result = mysqli_query($conn, $query);
if ($result==FALSE) {
    die("Error: ".mysqli_error($conn)."\n");
}
mysqli_select_db($conn,"$dbname");

// Add each table as needed
// ------------------------
$query  = "DROP TABLE tweeps; DROP TABLE connections;";
$result = mysqli_query($conn, $query);

// == TWEEPS =======
$table = "tweeps";
$query = "CREATE TABLE `tweeps` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `tweep_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `tweep_username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `tweep_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `description` blob,
          `followers` int(11) DEFAULT '0',
          `friends` int(11) DEFAULT '0',
          `tweets` int(11) DEFAULT '0',
          `verified` int(11) DEFAULT '0',
          `done` int(11) DEFAULT '0',
          `count` int(11) DEFAULT '0',
          `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT '0',
          `update_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT '0',
          PRIMARY KEY (`id`),
          UNIQUE KEY `tweep_id` (`tweep_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1587 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
make_table ($conn, $dbname, $table, $query);

// == connections =======
$table = "connections";
$query = "CREATE TABLE `connections` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `tweep_id` varchar(48) COLLATE utf8_unicode_ci DEFAULT NULL,
          `friend_id` varchar(48) COLLATE utf8_unicode_ci DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `friend_id` (`friend_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=11295888 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
make_table ($conn, $dbname, $table, $query);


// == network ===========
$table = "network";
$query = "CREATE TABLE `network` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `tweep_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `tweep_username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `tweep_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `description` blob,
          `verified` int(11) DEFAULT '0',
          `followers` int(11) DEFAULT '0',
          `friends` int(11) DEFAULT '0',
          `tweets` int(11) DEFAULT '0',
          `done` int(11) DEFAULT '0',
          `count` int(11) DEFAULT '0',
          `created_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT '0',
          `update_at` varchar(100) COLLATE utf8_unicode_ci DEFAULT '0',
          PRIMARY KEY (`id`),
          UNIQUE KEY `tweep_id` (`tweep_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=819541 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
make_table ($conn, $dbname, $table, $query);

// == combos ============
$table = "combos";
$query = "CREATE TABLE `combos` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `HubA_id` varchar(48) DEFAULT NULL,
          `HubA_name` varchar(48) DEFAULT NULL,
          `HubB_id` varchar(48) DEFAULT NULL,
          `HubB_name` varchar(48) DEFAULT NULL,
          `Count` int(11) DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `HubA` (`HubA_id`),
          KEY `HubB` (`HubA_name`)
        ) ENGINE=InnoDB AUTO_INCREMENT=351431 DEFAULT CHARSET=latin1;";
make_table ($conn, $dbname, $table, $query);



// ---------------------------
// FUNCTION: Create Table
// ---------------------------

function make_table($conn, $dbname, $table, $query) {
    $check_table = "SELECT table_name FROM information_schema.tables 
             WHERE table_schema = '$dbname' AND table_name = '$table'";
    $result = mysqli_query($conn, $check_table);
    if (mysqli_num_rows($result) == 0) {
        $result = mysqli_query($conn, $query);
        if ($result==FALSE) {
            die("Error: ".mysqli_error($conn)."\n");
        } else {
            echo "$table table created.\n";
        }
    } else echo "$table table exists.\n";
}

?>

