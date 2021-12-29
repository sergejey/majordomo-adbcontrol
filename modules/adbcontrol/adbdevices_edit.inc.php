<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}
$table_name = 'adbdevices';
$rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");

if ($this->tab == 'ops') {
    $op = gr('op');
    if ($op == 'run') {
        $activity = gr('activity');
        if ($activity!='') {
            $out['ACTIVITY']=$activity;
            $out['OUTPUT'] = $this->adbCommand($rec['IP'],'shell am start -n ' . $activity);
        }
    }
    if ($op == 'key') {
        $key = gr('key');
        if ($key != '') {
            $out['KEY'] = $key;
            $out['OUTPUT'] = $this->adbCommand($rec['IP'],'shell input keyevent ' . $key);

        }
    }
    if ($op == 'apk') {
        global $apk;
        global $apk_name;
        if (file_exists($apk)) {
            $filename = ROOT . 'cms/cached/' . $apk_name;
            move_uploaded_file($apk, $filename);
            $out['OUTPUT'] = $this->adbCommand($rec['IP'],'install –r ' . $filename);
        }
    }
    if ($op == 'processes') {
        $out['OUTPUT'] = $this->adbCommand($rec['IP'],'shell ps -A');
    }
    if ($op == 'packages') {
        $out['OUTPUT'] = $this->adbCommand($rec['IP'],'shell pm list packages -f');
    }
    if ($op == 'shell') {
        $cmd = gr('cmd');
        if ($cmd != '') {
            $out['CMD'] = $cmd;
            $out['OUTPUT'] = $this->adbCommand($rec['IP'],'shell ' . $cmd);
        }
    }
}

if ($this->mode == 'update') {
    $ok = 1;
    // step: default
    if ($this->tab == '') {
        //updating '<%LANG_TITLE%>' (varchar, required)
        $rec['IP'] = gr('ip');
        if (!$rec['IP']) {
            $ok = 0;
            $out['ERR_IP'] = 1;
        }
        $rec['TITLE'] = gr('title');
        if ($rec['TITLE'] == '') {
            $out['ERR_TITLE'] = 1;
            $ok = 0;
        }
    }
    // step: data
    if ($this->tab == 'data') {
    }
    //UPDATING RECORD
    if ($ok) {
        if ($rec['ID']) {
            SQLUpdate($table_name, $rec); // update
        } else {
            $new_rec = 1;
            $rec['ID'] = SQLInsert($table_name, $rec); // adding new record
        }
        $this->refreshDevice($rec['ID']);
        $out['OK'] = 1;
    } else {
        $out['ERR'] = 1;
    }
}
// step: default
if ($this->tab == '') {
}
// step: data
if ($this->tab == 'data') {
}
if ($this->tab == 'data') {
    //dataset2
    $new_id = 0;
    global $delete_id;
    if ($delete_id) {
        SQLExec("DELETE FROM adbproperties WHERE ID='" . (int)$delete_id . "'");
    }
    $properties = SQLSelect("SELECT * FROM adbproperties WHERE DEVICE_ID='" . $rec['ID'] . "' ORDER BY ID");
    $total = count($properties);
    for ($i = 0; $i < $total; $i++) {
        if ($properties[$i]['ID'] == $new_id) continue;
        if ($this->mode == 'update') {
            /*
            global ${'title' . $properties[$i]['ID']};
            $properties[$i]['TITLE'] = trim(${'title' . $properties[$i]['ID']});
            global ${'value' . $properties[$i]['ID']};
            $properties[$i]['VALUE'] = trim(${'value' . $properties[$i]['ID']});
            */
            global ${'linked_object' . $properties[$i]['ID']};
            $properties[$i]['LINKED_OBJECT'] = trim(${'linked_object' . $properties[$i]['ID']});
            global ${'linked_property' . $properties[$i]['ID']};
            $properties[$i]['LINKED_PROPERTY'] = trim(${'linked_property' . $properties[$i]['ID']});
            global ${'linked_method' . $properties[$i]['ID']};
            $properties[$i]['LINKED_METHOD'] = trim(${'linked_method' . $properties[$i]['ID']});
            SQLUpdate('adbproperties', $properties[$i]);
            $old_linked_object = $properties[$i]['LINKED_OBJECT'];
            $old_linked_property = $properties[$i]['LINKED_PROPERTY'];
            if ($old_linked_object && $old_linked_object != $properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property != $properties[$i]['LINKED_PROPERTY']) {
                removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
            }
            if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
                addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
            }
        }
    }
    $out['PROPERTIES'] = $properties;
}
if (is_array($rec)) {
    foreach ($rec as $k => $v) {
        if (!is_array($v)) {
            $rec[$k] = htmlspecialchars($v);
        }
    }
}
outHash($rec, $out);
