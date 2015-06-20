<?php


error_reporting(E_ALL);


include "DataSource.php";

$file="auto-01.kismet.netxml";
$csv_file = "auto-01.csv";



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

function InsertAP($connection, $AP_encryption,$AP_SSID,$AP_BSSID,$AP_manufacturer,$AP_networkChannel,$AP_networkFreq, $AP_LAT,$AP_LON,$DateLastSeen)
{
	$query_check_duplicate = mysql_query("SELECT * from aps where (ssid='".$AP_SSID."' AND bssid='".$AP_BSSID."') AND (ssid='' AND bssid!='')");
	
	if(mysql_num_rows($query_check_duplicate)!=1)
	{//echo mysql_num_rows($query_check_duplicate)."|".$AP_SSID."|".$AP_BSSID."\n";
		$query = "INSERT into aps (encryption,ssid,bssid,manufacturer,networkchannel,networkfreq,lat,lon,captured_on) VALUES('".$AP_encryption."','".$AP_SSID."','".$AP_BSSID."','".$AP_manufacturer."','".$AP_networkChannel."','".$AP_networkFreq."', '".$AP_LAT."','".$AP_LON."','".$DateLastSeen."') ON DUPLICATE KEY UPDATE encryption='".$AP_encryption."', ssid='".$AP_SSID."', bssid='".$AP_BSSID."', manufacturer='".$AP_manufacturer."', networkchannel='".$AP_networkChannel."', networkfreq='".$AP_networkFreq."',lat='".$AP_LAT."',lon='".$AP_LON."', captured_on='".$DateLastSeen."'";
		$query = mysql_query($query,$connection) or die("InsertAP: ".mysql_error());
		$query = mysql_query("SELECT id from aps where ssid='".$AP_SSID."' AND bssid='".$AP_BSSID."'") or die("ERR SELECT ID: ".mysql_error());
		return mysql_result($query,0,"id");
	}
	
	
}

function InsertClient($conn,$inserted_APid,$client_mac,$client_manuf,$client_channel,$latitude,$longitude)
{
		
		$query_check_inserted = mysql_query("SELECT idAP,clientMac from clients where clientMac='".$client_mac."'",$conn) or die("InsertClient: ".mysql_error());
		$numrows = mysql_num_rows($query_check_inserted);
		if($numrows==0)
		{
			$query = "INSERT into clients (idAP, clientMac, clientManufacturer,clientChannel,latitude,longitude) VALUES('".$inserted_APid."','".$client_mac."','".$client_manuf."','".$client_channel."','".$latitude."','".$longitude."')";
			$query = mysql_query($query,$conn) or die("InsertClient: ".mysql_error());
		}
}


function UpdateEmptyCoordinates($connection,$lastSeen)
{
	$query = "Select lat,lon from aps where (lat!='' and lon!='') OR (lat!='' and lon!='' AND captured_on='".$lastSeen."')";
	$execute_query = mysql_query($query,$connection) or die("ERROR UPDATE COORDINATES: ".mysql_error());
	$latitude = mysql_result($execute_query,0,"lat");
	$longitude = mysql_result($execute_query,0,"lon");
	
	$update_query = mysql_query("update aps set lat = '".$latitude."', lon='".$longitude."'");
	
	
}


$file = file_get_contents($file);


$file = str_replace("wireless-network","wirelessnetwork",$file);
$file = str_replace("wireless-client","wirelessclient",$file);
$file = str_replace("snr-info","snrinfo",$file);
$file = str_replace("cdp-device","cdpdevice",$file);
$file = str_replace("cdp-portid","cdpportid",$file);
$file = str_replace("first-time","firsttime",$file);
$file = str_replace("last-time","lasttime",$file);
$file = str_replace("max-rate","maxrate",$file);
$file = str_replace("client-mac","clientmac",$file);
$file = str_replace("client-manuf","clientmanuf",$file);
$file = str_replace("start-time","starttime",$file);
$file = str_replace("kismet-version","kismetversion",$file);
$file = str_replace("gps-info","gpsinfo",$file);
$file = str_replace("min-lat","minlat",$file);
$file = str_replace("min-lon","minlon",$file);
$file = str_replace("max-lat","maxlat",$file);
$file = str_replace("max-lon","maxlon",$file);
$file = str_replace("peak-lat","peaklat",$file);
$file = str_replace("peak-lon","peaklon",$file);
$file = str_replace("avg-lat","avglat",$file);
$file = str_replace("avg-lon","avglon",$file);
$wireless_data = simplexml_load_string($file);



$i=0;
foreach($wireless_data as $networkData)
{
	
	$networkEncryption 	= $networkData->SSID->encryption;
	$networkESSID 		= $networkData->SSID->essid;
	$networkBSSID 		= $networkData->BSSID;
	$manufacturer 		= $networkData->manuf;
	$networkChannel		= $networkData->channel;
	$networkFreq		= $networkData->freqmhz;
	
	$AP_GPS = $networkData->gpsinfo;
	$latitude = $AP_GPS->maxlat;
	$longitude = $AP_GPS->maxlon; 
	$conn=conn();
	$lastSeen = date("d-m-Y\tH:i:s");
	if($networkESSID=="" OR $networkESSID==NULL OR $networkESSID==" " or (!isset($networkESSID)))
	{
		$networkESSID="unknown_apXML_".$i;
	}
	$inserted_APid = InsertAP($conn, $networkEncryption,$networkESSID,$networkBSSID,$manufacturer, $networkChannel, $networkFreq, $latitude, $longitude,$lastSeen);

	$client_count = count($networkData->wirelessclient);
	if($client_count>0)
	{
		foreach ($networkData->wirelessclient as $client)
		{
			
			$client_mac = $client->clientmac;
			$client_manuf = $client->clientmanuf;
			$client_channel = $client->channel;
			
			InsertClient($conn,$inserted_APid,$client_mac,$client_manuf,$client_channel,$latitude,$longitude);
		}
	}
	++$i;
}



$csv_file = file_get_contents($csv_file);
$csv_file=str_replace(", ",",",$csv_file);
$csv_file=str_replace("   "," ",$csv_file);
$csv_file=str_replace("  "," ",$csv_file);
$csv_file=str_replace("  "," ",$csv_file);
$csv_file=str_replace(", ",",",$csv_file);
$csv_file=str_replace(" ,",",",$csv_file);
$csv_file=str_replace(". ",".",$csv_file);

file_put_contents("auto-02.csv",$csv_file);
$csv_file="auto-02.csv";



$conn=conn();
$csv = new File_CSV_DataSource;
if ($csv->load($csv_file)) 
{
	$array = $csv->getHeaders();
	$csv->getColumn($array);
	if ($csv->isSymmetric())
	{
		$array = $csv->connect();
	} 
	else 
	{
		$array = $csv->getAsymmetricRows();
	}
		$array = $csv->getrawArray();
		$array = array_values($array);
}
$array = array_filter($array);

$i=0;
$arr = array();
$arrKey="";
foreach($array as $DataARR=>$value)
{
	if($value[0]=="Station MAC")
	{
		$arrKey = $DataARR;
		break;
	}
}

while($arrKey<count($array))
{
	$arr[$i]=$array[($arrKey)];
	++$arrKey;
	++$i;
}

/*insert probed essid's with the clients that probed it*/
//print_R($arr);
foreach($arr as $key=>$value)
{
	if($key>0)
	{
		if(count($value)>=6)
		{
			for($i=6;$i<count($value);$i++)
			{
				if($value[$i]=="" OR $value[$i]==NULL OR $value[$i]==" " or (!isset($value[$i])))
				{
					$value[$i]="unknown_ap_".$i;
				}
				$InsAPID = InsertAP($conn, '',$value[$i],'','','','', '','',$lastSeen);
				InsertClient($conn,$InsAPID,$value[0],'','','','');
			}
		}
	}
}

$i=1;
$arr2 = array();

$arrKey="";
foreach($array as $DataARR=>$value)
{
	if($value[0]=="Station MAC")
	{
		$arrKey = $DataARR;
		break;
	}
}

while($i<$arrKey)
{
	$arr2[$i]=$array[($i)];
	
	++$i;
}
//print_R($arr);
for($i=2;$i<count($arr2);$i++)
{
	$csv_BSSID = $arr2[$i][0];
	$csv_First_time_seen = $arr2[$i][1];
	$csv_Last_time_seen = $arr2[$i][2];
	$csv_channel = $arr2[$i][3];
	$csv_encryption = $arr2[$i][5]." ".$arr2[$i][6]." ".$arr2[$i][7];
	$csv_ID_length = $arr2[$i][12];
	$csv_ESSID = $arr2[$i][13];
	$csv_ESSID = str_replace("'","\'",$csv_ESSID);
	
	if($arr2[$i][13]=="" OR $arr2[$i][13]==NULL OR $arr2[$i][13]==" " or (!isset($arr2[$i][13])) OR base64_encode($csv_ESSID)=="AA==")
	{
	
		$csv_ESSID="unknown_apCSV_".$i;
	}
	InsertAP($conn, $csv_encryption,$csv_ESSID,$csv_BSSID,"unknown", $csv_channel, "", "", "",$lastSeen);
}




UpdateEmptyCoordinates($conn,$lastSeen);


//function generateKMLForMap($conn)
//{
$name = "Capture_".date("Y-m-d\TH:i:s");
$description = "Dump Captured Data";
$xml = '<?xml version="1.0" encoding="UTF-8"?>';
$xml.='<kml xmlns="http://earth.google.com/kml/2.2">';
	$xml.='<Document>';
		$xml.='<name>'.$name.'</name>';
		$xml.='<description>'.$description.'</description>';
		
		
		$xml.='<Style id="style1">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/red-dot.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style2">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/red.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style3">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/yellow-dot.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style4">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/yellow.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style5">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/blue-dot.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style6">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/blue.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style7">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/green-dot.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>
			  <Style id="style8">
				<IconStyle>
				  <Icon>
					<href>http://maps.gstatic.com/mapfiles/ms2/micons/green.png</href>
				  </Icon>
				  <scale>1.000000</scale>
				</IconStyle>
			  </Style>';
	
	
		
		$query = "SELECT * from aps where bssid!='' and captured_on='".$lastSeen."'";
		$execute_query = mysql_query($query,$conn);
		
		$query_getMaxAP = mysql_result(mysql_query("SELECT count(bssid) as bssids from aps where bssid!=''",$conn),0,"bssids");
		$theta = 360/$query_getMaxAP;
		$radius=1;
		
		$i=0;
		$data=array();
		while($data[$i]=mysql_fetch_assoc($execute_query))
		{
			$ap_name = $data[$i]["ssid"];
			$ap_channel= $data[$i]["networkchannel"];
			//echo "\n";
			$encryption = $data[$i]["encryption"];
			//echo "\n";
			
			$mac_addr = $data[$i]["bssid"];
			
			$lat = $data[$i]["lat"];
			$lon = $data[$i]["lon"];
						
			$style="<styleUrl>#style1</styleUrl>";
			if($encryption=="OPN  ")
			{
				$style="<styleUrl>#style1</styleUrl>";
			}
			if($encryption=="WPA CCMP TKIP PSK")
			{
				$style="<styleUrl>#style4</styleUrl>";
			}
			if($encryption=="WPA2 WPA AES-CCM " || $encryption=="WPA2WPA TKIP PSK")
			{
				$style="<styleUrl>#style5</styleUrl>";
			}

			if($encryption=="WPA2 CCMP PSK" or $encryption=="WPA2 TKIP PSK")
			{
				$style="<styleUrl>#style3</styleUrl>";
			}
			if($encryption=="WPA2 CCMP TKIP PSK")
			{
				$style="<styleUrl>#style7</styleUrl>";
			}


			
			$xml.="<Placemark>";
			$xml.="<name>".$ap_name."</name>";
			$xml.="<description><![CDATA[There is a number of ".mysql_result(mysql_query("SELECT COUNT(idAP) as 'counter' from clients where idAP=".$data[$i]["id"]),0,'counter')." stations connected to this AP.\nBSSID = ".$mac_addr."\nEncryption=".$encryption."\nChannel: ".$ap_channel."]]></description>";
			$xml.=$style;
			$xml.="<Snippet>BSSID = ".$mac_addr."\nEncryption=".$encryption."\nChannel: ".$ap_channel."</Snippet>";
			$xml.="<Point>";
			$xml.="<coordinates>".$lon.",".$lat."</coordinates>
				</Point>";
			$xml.="</Placemark>";
			
			++$i;
		}
		
	//	print_R($data);
		
		/*
			
			
		*/
		
	
		
		
	$xml.='</Document>';
$xml.='</kml>';
//}

echo $xml;
?>