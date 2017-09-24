<?php

{
    if (sizeof($argv) != 6)
        die('usage: ' . $argv[0] . " <hostname> <user> <password> <database> <prefix>\n");

    $sql_hostname = $argv[1];
    $sql_user = $argv[2];
    $sql_password = $argv[3];
    $mambo_database_name = $argv[4];
    $table_prefix = $argv[5];
        
    $mambodb = mysql_connect($sql_hostname, $sql_user, $sql_password) or
    die ('Connection not possible : ' . mysql_error(). "\n");
    echo"Connected to the database\n" ;

    mysql_select_db($mambo_database_name, $mambodb) or
        die ('Can not find database ' .$mambo_database_name . ': ' . mysql_error(). "\n");

    /* __res_type */
    $data = array("normal", "cours de tennis", "manifestation", "réservation en cours");
    
    $table = $table_prefix . "_res_type";
    $res = mysql_query("create table if not exists $table (" .
   	"id int(11) not null primary key auto_increment, " .
   	"name varchar(25))", $mambodb);
    if(!$res)
        die("Could not create $table: " . mysql_error(). "\n");

    echo "$table ready\n";

    foreach ($data as $h => $name) {
    
        $res = mysql_query("SELECT id FROM $table WHERE NAME='$name'", $mambodb);
        $id = mysql_fetch_array($res);
        if ($id != null) {
            echo "*** found $id[0], skip\n";
            continue;
        }

        mysql_query("INSERT INTO $table (name) VALUES ('$name')", $mambodb) or
            die("failed to insert: " . mysql_error(). "\n");

        $res = mysql_query("SELECT id FROM $table WHERE NAME='$name'", $mambodb);
        $id = mysql_fetch_array($res);
        echo "added $id[0]\n";
    }
    
    /* __abo_type */
    $data = array("Famille", "Couple", "Adulte", "Etudiant", "Junior", 
    "Cadet", "Comite", "Membre d honneur");

    $table = $table_prefix . "_abo_type";
    $res = mysql_query("create table if not exists $table (" .
   	"id int(11) not null primary key auto_increment, " .
   	"name varchar(20))", $mambodb);
    if(!$res)
        die("Could not create $table: " . mysql_error(). "\n");

    echo "$table ready\n";
    
    foreach ($data as $h => $name) {
    
        $res = mysql_query("SELECT id FROM $table WHERE NAME='$name'", $mambodb);
        $id = mysql_fetch_array($res);
        if ($id != null) {
            echo "*** found $id[0], skip\n";
            continue;
        }

        mysql_query("INSERT INTO $table (name) VALUES ('$name')", $mambodb) or
            die("failed to insert: " . mysql_error(). "\n");

        $res = mysql_query("SELECT id FROM $table WHERE NAME='$name'", $mambodb);
        $id = mysql_fetch_array($res);
        echo "added $id[0]\n";
    }
}

?>
