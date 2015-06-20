<?php
error_reporting(E_ALL);



/**
*  Database Structure:
* 
* database: wificaptures
* username: root
* password: root
* 
* 
* tables:
* 	aps: id, AP_Mac
* 	aps_details: id, id_APs, Encryption_Type, Transmission_Channel, Frequency, GPS_Location, HandShake, Password, Cap_File_Location, DateTime
* 	aps_name: id, id_Aps, Network_Name
* 	sniffed_stations id, id_Ap, id_Network_Name,Station_Mac, Station_Power
* 
* 					sniffed_stations.id_Ap
* 					/\			
* 					||			
* 					||			
* relationships: aps.id ===>aps_details.id_APs
* 					||		aps_details.id===>sniffed_station.id_Network_Name
* 					||
* 					\/
* 					aps_name.id_Aps
* 
* 					
* 
*/


function Get_AP_Id($connection,$table,$AP_Mac)
{
	$query = 'SELECT id from '.$table.' where AP_Mac="'.$AP_Mac.'"';
	$exec = mysql_query($query,$connection);
	if(mysql_num_rows($exec)>0)
	{
		$network_id = mysql_result($exec,0,"id");	
	}
	else
	{
		$network_id="inexistent";
	}
	
	return $network_id;
}
function Get_Network_Id($connection,$table,$Network_Name)
{
	$query = 'SELECT id from '.$table.' where Network_Name="'.$Network_Name.'"';
	$exec = mysql_query($query,$connection);
	if(mysql_num_rows($exec)>0)
	{
		$network_id = mysql_result($exec,0,"id");	
	}
	else
	{
		$network_id = "inexistent";
	}
	
	return $network_id;
}

function Get_Network_AP_Id($connection,$table,$Network_Name)
{
	$query = 'SELECT id_Aps from '.$table.' where Network_Name="'.$Network_Name.'"';
	$exec = mysql_query($query,$connection);
	if(mysql_num_rows($exec)>0)
	{
		$network_id = mysql_result($exec,0,"id_Aps");	
	}
	else
	{
		$network_id = "inexistent";
	}
	
	return $network_id;
}

function Insert_AP($connection,$table,$AP_Mac)
{
	$query = 'insert into '.$table.' (AP_Mac) values ("'.$AP_Mac.'")';
	$exec = mysql_query($query,$connection);
	
}

function Insert_AP_Name($connection,$table,$AP_id,$Network_Name)
{
	$query = 'insert into '.$table.' (id_Aps,Network_Name) values ("'.$AP_id.'","'.$Network_Name.'")';
	$exec = mysql_query($query,$connection);
}
function Insert_AP_Details($connection,$table,$id_APs,$Encryption_Type,$Transmission_Channel, $Frequency, $GPS_Location, $HandShake, $Password, $Cap_File_Location, $DateTime)
{
	$query = 'insert into '.$table.' (id_Aps,Encryption_Type,Transmssion_Channel,Frequency,GPS_Location,Handshake,Password,Cap_File_Location,DateTime) values ("'.$id_APs.'","'.$Encryption_Type.'","'.$Transmission_Channel.'","'.$Frequency.'","'.$GPS_Location.'","'.$HandShake.'","'.$Password.'","'.$Cap_File_Location.'","'.$DateTime.'")';
	
	mysql_query($query,$connection) or die(mysql_error());
}
function Insert_Network_Name_with_Mac($connection,$table,$id_Aps,$Network_Name)
{
	$query = 'insert into '.$table.' (id_Aps,Network_Name) values ("'.$id_APs.'","'.$Network_Name.'")';
	
	$exec = mysql_query($query,$connection);
}
function Insert_Network_Name_without_Mac($connection,$table,$Network_Name)
{
	$query = 'insert into '.$table.' (Network_Name) values ("'.$Network_Name.'")';
	
	mysql_query($query,$connection) or die("Insert_Network_Name_without_Mac".mysql_error()." ".$query);
}
function Insert_Station_with_APMac($connection,$table, $Station_Mac, $Station_Power,$id_Ap="",$id_Network_Name="")
{
	$query = "insert into ".$table." (id_Ap,id_Network_Name,Station_Mac,Station_Power) values ('".$id_Ap."','".$id_Network_Name."','".$Station_Mac."','".$Station_Power."')";
	
	mysql_query($query,$connection) or die("Insert_Station_with_APMac\n".mysql_error()."\n".$query);
	
}
function Insert_Station_without_APMac($connection,$table,$id_Network_Name,$Station_Mac,$Station_Power)
{
	$query = "insert into ".$table." (id_Network_Name,Station_Mac,Station_Power) values ('".$id_Network_Name."','".$Station_Mac."','".$Station_Power."')";
	
	mysql_query($query,$connection) or die("Insert_Station_with_APMac".mysql_error());
}









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


function read_csv($csv_file)
{
	
	$data = file_get_contents($csv_file);
	$data = explode("Station MAC, First time seen, Last time seen, Power, # packets, BSSID, Probed ESSIDs",$data);
	$data = $data[1];
	$data = explode("\n",$data);
	arsort($data);
	$data = array_filter($data);
	$data = array_values($data);
	foreach($data as $key=>$value)
	{
		if(strlen($value)<=1)
		{
			unset($data[$key]);
		}
		else
		{
			//$data[$key]=str_replace(" ","##",$data[$key]);
			//$data[$key] = substr($data[$key],0,-3);
			$data[$key] = str_replace("  "," ",$data[$key]);
			$data[$key] = str_replace("    "," ",$data[$key]);
			$data[$key] = str_replace(" ,",", ",$data[$key]);
			//print_r($data[$key]);
			$data[$key] = explode(", ",$data[$key]);
			//print_r($data[$key]);
			//echo "\n\n";
			
			$collected_data["AP_MAC"][]=str_replace(",","",$data[$key][5]);
			$collected_data["Station_MAC"][]=$data[$key][0];
			$collected_data["Station_WiFi_Power"][]=$data[$key][3];
			$collected_data["Probed_Networks"][]=$data[$key][6];
			
			//break;
			//print_r($data[$key][6]);echo "\n";
			/*$data[$key][5] = str_replace(" ","",$data[$key][5]);
			$data[$key][5] = str_replace(",","",$data[$key][5]);
			$data[$key][3] = str_replace(" ","",$data[$key][3]);
			$data[$key][4] = str_replace(" ","",$data[$key][4]);
			if($data[$key][5]=="(notassociated)")
			{
				$data[$key][5] = "LipsaMac";
			}
			
			$collected_data[$data[$key][5]][]=$data[$key][0];
			//$collected_data[$data[$key][5]][]["History"]=explode(",",$data[$key][6]);*/
			
		}
	}
	
	
	foreach($collected_data as $key=>$value)
	{
		for($i=0;$i<count($value);$i++)
		{
			$date_ref[$i][]=$collected_data[$key][$i];
		}
	}
	$collected_data=$date_ref;
	//print_r($collected_data);
	//die();
	return $collected_data;
}

function read_xml($xml_file)
{
	$data = simplexml_load_file($xml_file);
	$apname="";
	$encryptie="";
	$mac="";
	$canal="";
	$frecventa="";
	foreach ($data as $key=>$value)
	{
		$apname.= $value->SSID->essid."#";
		$encryptie.= $value->SSID->encryption."#";
		$mac.= $value->BSSID."#";
		$canal .= $value->channel."#";
		$frecventa .= $value->freqmhz."#";
	}
	//print_r($apname);
	$apname=explode("#",$apname);
	$apname = array_filter($apname);
	
	$encryptie=explode("#",$encryptie);
	$encryptie = array_filter($encryptie);
	
	$mac=explode("#",$mac);
	$mac = array_filter($mac);
	
	$canal=explode("#",$canal);
	$canal = array_filter($canal);
	
	$frecventa=explode("#",$frecventa);
	$frecventa = array_filter($frecventa);
	
	$storage["Netowrk_Name"]=$apname;
	$storage["AP_Mac"]=$mac;
	$storage["Encryption"]=$encryptie;
	$storage["Channel"]=$canal;
	$storage["Frequency"]=$frecventa;
	
	for($i=0;$i<count($storage["Netowrk_Name"]);$i++)
	{
		$data_storage[$storage["AP_Mac"][$i]][]=$storage["Netowrk_Name"][$i];
		$data_storage[$storage["AP_Mac"][$i]][]=$storage["Encryption"][$i];
		$data_storage[$storage["AP_Mac"][$i]][]=$storage["Channel"][$i];
		$data_storage[$storage["AP_Mac"][$i]][]=$storage["Frequency"][$i];
	}
	
return $data_storage;
	
}

function get_files($directory)
{
	$scanned_directory = array_values(array_diff(scandir($directory), array('..', '.')));
	print_r($scanned_directory);
	//die();
	$file["csv"]=$directory.$scanned_directory[1];
	$file["xml"]=$directory.$scanned_directory[2];
	return $file;
}


	
function combine_data($Sniffed_APs,$Sniffed_Stations)
{
	print_R($Sniffed_APs);
	
	//print_R($Sniffed_Stations);
	$conn=conn();
	
	$GPS_Location="";
	$HandShake="";
	$Cap_File_Location="";
	$Password = "";
	foreach($Sniffed_APs as $key=>$value)
	{
		$AP_Mac = $key;
		$AP_Network_Name = $value[0];
		$AP_Encryption_type = $value[1];
		$AP_Channel = $value[2];
		$AP_Frequency = $value[3];
		
		//$AP_Mac." | ".$AP_Network_Name." | ".$AP_Encryption_type." | ".$AP_Channel." | ".$AP_Frequency."<br />";
		$existing_Mac_id = Get_AP_Id($conn,"aps",$AP_Mac);
		
		if($existing_Mac_id=="inexistent")
		{
			echo "here";
			Insert_AP($conn,"aps",$AP_Mac);
			$existing_Mac_id2 = Get_AP_Id($conn,"aps",$AP_Mac);
			
			//if(strlen($AP_Network_Name)<=1)
			//{
			//	$AP_Network_Name = "????";
			//}
			//if($AP_Network_Name!="????")
			//{
			//check if network already exists
				$network_exist_id = Get_Network_Id($conn,"aps_name",$AP_Network_Name);
				if($network_exist_id=="inexistent")
				{
					Insert_AP_Name($conn,"aps_name",$existing_Mac_id2,$AP_Network_Name);
				}
				else
				{
					//update the ap_id
					$existing_Mac_id2 = Get_AP_Id($conn,"aps",$AP_Mac);

				
				
					$query = "update aps_name set id_Aps='".$existing_Mac_id2."' where Network_Name='".$AP_Network_Name."'";
					mysql_query($query,$conn);
				
				
				}
			
			
			
			
				/*if(strlen($AP_Encryption_type)<=1)
				{
					$AP_Encryption_type = "unknown";
				}
				if($AP_Channel<1)
				{
					$AP_Channel = "unknown";
				}
				echo $AP_Channel;
				*/
				//if($AP_Channel!="unknown")
				//{
					$GPS_Location = "0,0";
					//echo $GPS_Location = shell_exec("./test_gps.py");
					Insert_AP_Details($conn,"aps_details",$existing_Mac_id2,$AP_Encryption_type,$AP_Channel, $AP_Frequency, $GPS_Location, $HandShake, $Password, $Cap_File_Location, date("Y-m-d H:i:s"));	
				//}
			//}
		}
	}
	
	foreach($Sniffed_Stations as $num_of_stations=>$station_details)
	{
		$Assoc_Station_with_mac = $station_details[0];
		$Assoc_Station_MAC = $station_details[1];
		$Assoc_Station_Power = $station_details[2];
		
		$Assoc_Station_with_Network_Name = explode(",",$station_details[3]);
		
		
		/**
		* case1: nu am pt o statie asociat un mac si nu am asociat si nici un nume pt ap
		* case2: nu am pt o statie asociat un mac dar am asociat un nume sau o lista de nume
		* case3: am pt o statie asociat un apmac dar nu am asociat un nume pentru ap
		* case4: am pentru o statie asociat un mac si am asociat si un nume pentru ap / lista de nume
		* 
		*/
		
		
		foreach($Assoc_Station_with_Network_Name as $counter=>$names)
		{
			$existing_Mac_id2 = Get_AP_Id($conn,"aps",$Assoc_Station_with_mac);
			$names=str_replace("\r","",$names);
			$names=str_replace("\n","",$names);
			$names="###".$names;
			$names = str_replace("### ","",$names);
			$names = str_replace("###","",$names);
			//echo $names;
			if(strlen($names)>1)
			{
				$network_exist_id = Get_Network_Id($conn,"aps_name",$names);
				if($network_exist_id=="inexistent")
				{
						Insert_Network_Name_without_Mac($conn,"aps_name",$names);					
				}
					
				$query="SELECT id from aps_name where Network_Name='".$names."' AND id_Aps='0'";
				$exec_query = mysql_query($query,$conn);
				$id_net_name = intval(mysql_result($exec_query,"0","id"));
				
				if($id_net_name!=0)
				{
					$query = "SELECT id from sniffed_stations where Station_Mac='".$Assoc_Station_MAC."' AND id_Network_Name='".$id_net_name."' AND id_AP='0'";
					$data = mysql_query($query,$conn);
					if(mysql_num_rows($data)==0)
					{
						Insert_Station_with_APMac($conn,"sniffed_stations", $Assoc_Station_MAC, $Assoc_Station_Power,NULL,$id_net_name);	
					}
					
				}
				
				$query="SELECT id,id_APs from aps_name where Network_Name='".$names."' AND id_Aps!='0'";
				$exec_query = mysql_query($query,$conn);
				$id_net_name = intval(mysql_result($exec_query,"0","id"));
				$id_APs = intval(mysql_result($exec_query,"0","id_APs"));
				if($id_net_name!=0 AND $id_APs!=0)
				{
					$query = "SELECT id from sniffed_stations where Station_Mac='".$Assoc_Station_MAC."' AND id_Network_Name='".$id_net_name."' AND id_AP='".$id_APs."'";
					$data = mysql_query($query,$conn);
					if(mysql_num_rows($data)==0)
					{
						Insert_Station_with_APMac($conn,"sniffed_stations", $Assoc_Station_MAC, $Assoc_Station_Power,$id_APs,$id_net_name);	
					}
					
				}
				
				//get the network where the mac !=0
			
			}
			
			
			
			
			
			
			
			
			/*if($network_exist_id!="inexistent")
			{
				$nework_id = Get_Network_Id($conn,"aps_name",$names);
				if($nework_id=="inexistent")
				{
					Insert_Network_Name_without_Mac($conn,"aps_name",$names);
					$nework_id = Get_Network_Id($conn,"aps_name",$names);
					if($nework_id=="inexistent")
					{
						Insert_Station_with_APMac($conn,"sniffed_stations",$existing_Mac_id2 ,$nework_id,$Assoc_Station_MAC,$Assoc_Station_Power);	
					}
					
				}
				
				
				
			}
			else
			{
				if($network_exist_id=="inexistent")
				{
					if(strlen($names)>1)
					{
						
						Insert_Network_Name_without_Mac($conn,"aps_name",$names);
					}
					$nework_id = Get_Network_Id($conn,"aps_name",$names);
					if($network_id=="inexistent")
					{
						Insert_Station_without_APMac($conn,"sniffed_stations",$nework_id,$Assoc_Station_MAC,$Assoc_Station_Power);
					}
					else
					{
						if($network_id!="inexistent")
						{
							Insert_Station_with_APMac($conn,"sniffed_stations",$existing_Mac_id2 ,$nework_id,$Assoc_Station_MAC,$Assoc_Station_Power);
						}
						else
						{
							continue;
						}
							
					}
					
				}
			}*/
			
			
		}
		echo "\n\n***********\n\n";
		
		
		//echo $Assoc_Station_with_mac."|".$Assoc_Station_MAC."|".$Assoc_Station_Power."|".$Assoc_Station_with_Network_Name."<br/>";		
	}
	die();
	//	return $Sniffed_APs;
	
}
	
function process_data()
{
	$directory ="capture_files/";
	$files = get_files($directory);
	$xml_data = read_xml($files["xml"]);
	$csv_data = read_csv($files["csv"]);
	
	
	combine_data($xml_data,$csv_data);
	
}

process_data();



function GET_List($connexiune)
{
	
}


?>
