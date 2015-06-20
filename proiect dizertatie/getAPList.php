<?php
error_reporting(E_ALL);
function conn()
{
        $mysqldb="wificaptures";
        $mysqluser="root";
        $mysqlpass="root";
        $connection = mysql_connect("localhost", $mysqluser, $mysqlpass);
        if(!$connection)
        {
                $error="ERROR CONN DB.".mysql_error();
                return $error;
        }
        else
        {
                $db_select = mysql_select_db($mysqldb,$connection);
                if(!$db_select)
                {
                        $error.="ERROR SELECTING DATABASE".mysql_error();
                        return $error;
                }
                else
                {
                        return $connection;
                }
        }
}
$date="";
$conn=conn();
$data = "SELECT Network_Name from aps_name";
$exec = mysql_query($data,$conn);
$i=0;
$data = array();
while($data[$i]=mysql_fetch_assoc($exec))

{	//print_r($data);
	//die();
	$date.=$data[$i]["Network_Name"]."\n";
	++$i;
}
file_put_contents("ap_list",$date);



?>
