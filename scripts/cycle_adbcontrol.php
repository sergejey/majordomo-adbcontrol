<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'adbcontrol/adbcontrol.class.php');
$adbcontrol_module = new adbcontrol();
$adbcontrol_module->getConfig();
$tmp = SQLSelectOne("SELECT ID FROM adbdevices LIMIT 1");
if (!$tmp['ID'])
   exit; // no devices added -- no need to run this cycle
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

$checkEvery=(int)$adbcontrol_module->config['POLL_DEVICES'];
if (!$checkEvery) {
    $checkEvery = 5;
}
$latest_check=0;

$restartEvery = (int)$adbcontrol_module->config['RESTART_ADB'];
if (!$restartEvery) {
    $restartEvery = 60*60;
}
$latest_restart = time();

while (1)
{
   setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);

    if ((time()-$latest_restart)>$restartEvery) {
        $latest_restart=time();
        echo date('Y-m-d H:i:s').' Restarting ADB...';
        $adbcontrol_module->restartAdb();
    }

   if ((time()-$latest_check)>$checkEvery) {
    $latest_check=time();
    echo date('Y-m-d H:i:s').' Polling devices...';
    $adbcontrol_module->processCycle();
   }

   if (file_exists('./reboot') || IsSet($_GET['onetime']))
   {
      $db->Disconnect();
      exit;
   }
   sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
