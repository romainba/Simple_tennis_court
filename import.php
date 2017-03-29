<?php

function toascii( $str )
{
    return strtr($str,
    'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ',
    'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}

{
    $sql_hostname = 'localhost';
    $sql_user = 'admin';
    $sql_password = '';
    $mambo_database_name = 'test'; //'joomla';
    $table_prefix = 'c8iu9';

    $passwd = '$2y$10$U9c5WD14h.IsMHYj1LCseeVlH4Cdzv7eHBkEW2bOps6y.dZ9W8Zwm'; // tclv

    $path_to_csv = 'list.csv'; //this is the name of your csv file, or text file
    $csv_delimiter = ","; //choose your delimiter

    $mambodb = mysql_connect($sql_hostname, $sql_user, $sql_password) or
    die ('Connection not possible : ' . mysql_error(). "\n");
    echo"Connected to the database\n" ;

    mysql_select_db($mambo_database_name, $mambodb) or
        die ('Can not find database ' .$mambo_database_name . ': ' . mysql_error(). "\n");

    $table = $table_prefix . '_users';

    $handle = fopen ($path_to_csv,"r");

    # create reservation table
    $name = $table_prefix . "_reservation";
    $res = mysql_query("create table if not exists $name (" .
   	"id int(11) not null primary key auto_increment, " .
   	"user int(11) not null, partner int(11) not null, " .
    "date datetime not null)", $mambodb);
    if(!$res)
        die("Could not create $name: " . mysql_error(). "\n");

    $g = 100;

    while ($data = fgetcsv ($handle, 1000, $csv_delimiter)) {
        
        $group = $data[0];
        if ($group == '')
            $group = $g++;

        $firstname = str_replace(' ', '', mysql_escape_string($data[3]));
        $lastname = str_replace(' ', '', mysql_escape_string($data[2]));
        $name = "$firstname $lastname";

        $username = "$firstname.$lastname";
        $username = strtolower($username);
        $username = str_replace(' ', '', $username);
        $username = toascii($username);
    
        $email = mysql_escape_string($data[6]);

        $abonnement = mysql_escape_string($data[8]);
        $categorie = mysql_escape_string($data[9]);
        $naissance = date_create_from_format('m/j/Y', mysql_escape_string($data[10]));

        if ($naissance != null)
            $n = $naissance->format('Y-m-d');
        else
            $n = "";
        
        echo "$group, $username, $name,  $email, $abonnement, $categorie, $n\n";

        $res = mysql_query("SELECT id FROM $table WHERE NAME='$name'", $mambodb);
        $id = mysql_fetch_array($res);
        if ($id != null) {
            echo "*** found $id[0], skip\n";
            continue;
        }

        mysql_query("INSERT INTO $table (name, username, email, password, requireReset, " .
        "naissance, abonnement, group_id) " .
    	"VALUES ('$name', '$username', '$email', '$passwd', '1', '$n', '$abonnement', '$group')",
    	$mambodb) or die("failed to insert: " . mysql_error(). "\n");

        $res = mysql_query("SELECT id FROM $table WHERE NAME='$name'", $mambodb);
        $id = mysql_fetch_array($res);
        echo "added $id[0]\n";

        # register only users with host variable set
        mysql_query("INSERT INTO " .$table_prefix. "_user_usergroup_map (user_id, group_id) ".
    	"VALUES ('$id[0]', '2')",
    	$mambodb) or die("failed to insert: " . mysql_error(). "\n");
    }

    fclose ($handle);

}

?>
