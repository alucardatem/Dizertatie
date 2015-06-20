<?php 

$command_check = "sudo ps aux | grep \"/bin/bash ./autoscan\" | grep -v grep";
$execute_command = shell_exec($command_check);
$processes = explode("\n",$execute_command);
$processes = array_filter($processes);

$processes=$processes[0];
$processes = str_replace(" ","#",$processes);
$processes = str_replace("#####","#",$processes);
$processes = str_replace("####","#",$processes);
$processes = str_replace("###","#",$processes);
$processes = str_replace("##","#",$processes);
$processes = str_replace("#","##",$processes);

$processes = explode("##",$processes);
$pid = $processes[1];
shell_exec("sudo kill -9 ".$pid);
sleep(1);
shell_exec("sudo airmon-ng stop mon0");
sleep(3);

shell_exec("rm /var/www/aircrack-capture/capture_files/*");
//echo $processes;
?>
