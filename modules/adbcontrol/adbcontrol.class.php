<?php
/**
 * ADB control
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 20:12:22 [Dec 28, 2021])
 */
//
//
class adbcontrol extends module
{
    /**
     * adbcontrol
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "adbcontrol";
        $this->title = "ADB control";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->data_source)) {
            $p["data_source"] = $this->data_source;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $data_source;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($data_source)) {
            $this->data_source = $data_source;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['DATA_SOURCE'] = $this->data_source;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        $this->getConfig();

        $out['RESTART_ADB'] = (int)$this->config['RESTART_ADB'];
        if (!$out['RESTART_ADB']) {
            $out['RESTART_ADB'] = 60*60;
        }
        $out['POLL_DEVICES'] = (int)$this->config['POLL_DEVICES'];
        if (!$out['POLL_DEVICES']) {
            $out['POLL_DEVICES']=5;
        }
        $out['LOG_ENABLED'] = (int)$this->config['LOG_ENABLED'];

        if ($this->view_mode == 'update_settings') {

            $this->config['RESTART_ADB'] = gr('restart_adb','int');
            $this->config['POLL_DEVICES'] = gr('poll_devices','int');
            $this->config['LOG_ENABLED'] = gr('log_enabled','int');
            $this->saveConfig();

            $service = 'cycle_adbcontrol';
            sg($service . 'Run', '');
            sg($service . 'Control', 'restart');

            $this->redirect("?");
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'adbdevices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_adbdevices') {

                exec('adb --version',$result);
                $version = implode('',$result);
                //$version = $this->adbCommand('', '--version');
                $out['VERSION'] = $version;

                $this->search_adbdevices($out);
            }
            if ($this->view_mode == 'edit_adbdevices') {
                $this->edit_adbdevices($out, $this->id);
            }
            if ($this->view_mode == 'delete_adbdevices') {
                $this->delete_adbdevices($this->id);
                $this->redirect("?data_source=adbdevices");
            }
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'adbproperties') {
            if ($this->view_mode == '' || $this->view_mode == 'search_adbproperties') {
                $this->search_adbproperties($out);
            }
            if ($this->view_mode == 'edit_adbproperties') {
                $this->edit_adbproperties($out, $this->id);
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * adbdevices search
     *
     * @access public
     */
    function search_adbdevices(&$out)
    {
        require(dirname(__FILE__) . '/adbdevices_search.inc.php');
    }

    /**
     * adbdevices edit/add
     *
     * @access public
     */
    function edit_adbdevices(&$out, $id)
    {
        require(dirname(__FILE__) . '/adbdevices_edit.inc.php');
    }

    /**
     * adbdevices delete record
     *
     * @access public
     */
    function delete_adbdevices($id)
    {
        $rec = SQLSelectOne("SELECT * FROM adbdevices WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM adbproperties WHERE DEVICE_ID='" . $rec['ID'] . "'");
        SQLExec("DELETE FROM adbdevices WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * adbproperties search
     *
     * @access public
     */
    function search_adbproperties(&$out)
    {
        require(dirname(__FILE__) . '/adbproperties_search.inc.php');
    }

    function refreshDevice($id)
    {
        $device = SQLSelectOne("SELECT * FROM adbdevices WHERE ID=" . (int)$id);
        if (!$device['ID']) return;
        $ip = $device['IP'];

        $res = $this->adbCommand($ip, 'connect ' . $ip, 1);
        if (!preg_match('/connected/', $res)) return false;

        $device_updated = 0;

        $currentWindow = $this->getCurrentWindow($ip);
        if ($currentWindow != '') {
            $this->updateProperty($id, 'currentWindow', $currentWindow);
            $device_updated = 1;
        }

        $uptime = $this->getUptime($ip);
        if ($uptime) {
            $device_updated = 1;
            $this->updateProperty($id, 'uptime', $uptime);
        }


        $info = $this->getBatteryInfo($ip);
        foreach($info as $k=>$v) {
            $this->updateProperty($id, $k, $v);
            $device_updated = 1;
        }

        $this->updateProperty($id, 'keyCode');
        $this->updateProperty($id, 'runActivity');

        if ($device_updated) {
            SQLExec("UPDATE adbdevices SET UPDATED='".date('Y-m-d H:i:s')."' WHERE ID=".(int)$id);
        }


    }

    function updateProperty($device_id, $title, $value = '[undefined]')
    {
        $rec = SQLSelectOne("SELECT * FROM adbproperties WHERE DEVICE_ID=" . $device_id . " AND TITLE='" . DBSafe($title) . "'");
        $rec['DEVICE_ID'] = $device_id;
        $rec['TITLE'] = $title;

        if ($value=='[undefined]') {
            $value = $rec['VALUE'];
        }

        if ($rec['VALUE'] != $value || !$rec['ID']) {
            $rec['VALUE'] = $value;
            $rec['UPDATED'] = date('Y-m-d H:i:s');
            if ($rec['LINKED_OBJECT'] != '' && $rec['LINKED_PROPERTY'] != '') {
                sg($rec['LINKED_OBJECT'] . '.' . $rec['LINKED_PROPERTY'], $value, array('adbcontrol' => '0'));
            }
            if ($rec['LINKED_OBJECT'] != '' && $rec['LINKED_METHOD'] != '') {
                callMethod($rec['LINKED_OBJECT'] . '.' . $rec['LINKED_PROPERTY'], array('VALUE' => $value, 'NEW_VALUE' => $value));
            }
        }
        if (!$rec['ID']) {
            SQLInsert('adbproperties', $rec);
        } else {
            SQLUpdate('adbproperties', $rec);
        }
    }

    function getBatteryInfo($ip) {
        $info = array();
        $res = $this->adbCommand($ip, 'shell dumpsys battery', 1);

        if (preg_match('/level: (\d+)/is',$res,$m)) {
            $info['battery_level']=$m[1];
        }
        if (preg_match('/AC powered: (\w+)/is',$res,$m)) {
            if ($m[1]=='true') {
                $info['ac_powered']=1;
            } else {
                $info['ac_powered']=0;
            }
        }
        return $info;
    }

    function getUptime($ip) {
        $res = $this->adbCommand($ip,'shell uptime', 1);
        if (preg_match('/up time: ([\d:]+)/is',$res,$m)) {
            return $m[1];
        }
        return '';
    }

    function getCurrentWindow($ip)
    {
        $res = $this->adbCommand($ip, 'shell dumpsys window windows | grep -E \'mCurrentFocus|mFocusedApp\'', 1);
        $title = '';
        if (preg_match('/mCurrentFocus=(.+)$/i', $res, $m)) {
            $title = $m[1];
        }
        if (preg_match('/mFocusedApp=(.+)$/i', $res, $m)) {
            $title = $m[1];
        }
        if (preg_match('/com\.[\.\w\d\/]+/', $title, $m)) {
            $title = $m[0];
        }

        return $title;

    }

    function adbCommand($ip, $cmd, $ignore_log = 0)
    {
        $output = array();
        exec('adb connect ' . $ip, $output, $code);

        $output = array();
        $code = 0;

        exec('adb -s ' . $ip . ' ' . $cmd . ' 2>&1', $output, $code);
        $output_text = implode("\n", $output);
        if ($code && preg_match('/error: closed/',$output_text)) {
            debmes('Restarting adb: adb kill-server','adbcontrol');
            exec('adb kill-server');
            sleep(1);
            exec('adb connect ' . $ip, $output, $code);
            $output = array();
            $code = 0;
            exec('adb -s ' . $ip . ' ' . $cmd . ' 2>&1', $output, $code);
            $output_text = implode("\n", $output);
        }

        if ($this->config['LOG_ENABLED'] && !$ignore_log) {
            debmes('adb -s ' . $ip . ' ' . $cmd,'adbcontrol');
            debmes('Code ' . $code . ': ' . $output_text,'adbcontrol');
        }
        if (!$code) {
            $result = implode("\n", $output);
        } else {
            $result = '';
        }

        return $result;
    }

    function restartAdb() {
        exec('adb kill-server');
    }

    /**
     * adbproperties edit/add
     *
     * @access public
     */
    function edit_adbproperties(&$out, $id)
    {
        require(dirname(__FILE__) . '/adbproperties_edit.inc.php');
    }

    function api($params) {


        $rec = SQLSelectOne("SELECT * FROM adbdevices WHERE ID=".(int)$_REQUEST['id']);

        if (!$rec['ID']) return;



        $result = '';
        if ($_REQUEST['op']=='shell') {
            $result = $this->adbCommand($rec['IP'],'shell ' . $_REQUEST['data']);
        }
        if ($_REQUEST['op']=='key') {
            $result = $this->adbCommand($rec['IP'],'shell input keyevent ' . $_REQUEST['data']);
        }
        if ($_REQUEST['op']=='run') {
            $result = $this->adbCommand($rec['IP'],'shell am start -n ' . $_REQUEST['data']);
        }
        return $result;
    }

    function propertySetHandle($object, $property, $value)
    {
        $this->getConfig();
        $properties = SQLSelect("SELECT adbproperties.ID, adbproperties.DEVICE_ID, adbproperties.TITLE, adbdevices.IP FROM adbproperties LEFT JOIN adbdevices ON adbdevices.ID=adbproperties.DEVICE_ID WHERE adbproperties.LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND adbproperties.LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($properties);
        if ($total) {
            for ($i = 0; $i < $total; $i++) {
                SQLExec("UPDATE adbproperties SET VALUE='".DBSafe($value)."', UPDATED='".date('Y-m-d H:i:s')."' WHERE ID=".$properties[$i]['ID']);
                if ($properties[$i]['TITLE']=='keyCode') {
                    $res = $this->adbCommand($properties[$i]['IP'],'shell input keyevent ' . $value);
                }
                if ($properties[$i]['TITLE']=='runActivity') {
                    $res = $this->adbCommand($properties[$i]['IP'],'shell am start -n ' . $value);
                }
            }
        }
    }

    function processCycle()
    {
        $this->getConfig();
        $devices=SQLSelect("SELECT ID FROM adbdevices");
        $total = count($devices);
        for($i=0;$i<$total;$i++) {
            $this->refreshDevice($devices[$i]['ID']);
        }

    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS adbdevices');
        SQLExec('DROP TABLE IF EXISTS adbproperties');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        adbdevices -
        adbproperties -
        */
        $data = <<<EOD
 adbdevices: ID int(10) unsigned NOT NULL auto_increment
 adbdevices: TITLE varchar(100) NOT NULL DEFAULT ''
 adbdevices: IP varchar(100) NOT NULL DEFAULT ''
 adbdevices: UPDATED datetime
 
 adbproperties: ID int(10) unsigned NOT NULL auto_increment
 adbproperties: TITLE varchar(100) NOT NULL DEFAULT ''
 adbproperties: VALUE varchar(255) NOT NULL DEFAULT ''
 adbproperties: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 adbproperties: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 adbproperties: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 adbproperties: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 adbproperties: UPDATED datetime
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRGVjIDI4LCAyMDIxIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
