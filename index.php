<?php

require_once 'moodle.php';
require_once 'config.php';
//require_once '../moodle/config.php';
//require_once '../moodle/local/secretaria/lib.php';

$functions = array(
    'get_user' => array(
        'username' => 'str',
    ),
    'get_user_lastaccess' => array(
        'users' => array(),
    ),
    'create_user' => array(
        'properties' => array(
            'username' => 'str?',
            'password' => 'str?',
            'firstname' => 'str?',
            'lastname' => 'str?',
            'email' => 'str?',
        ),
    ),
    'update_user' => array(
        'username' => 'str',
        'properties' => array(
            'username' => 'str?',
            'password' => 'str?',
            'firstname' => 'str?',
            'lastname' => 'str?',
            'email' => 'str?',
        ),
    ),
    'delete_user' => array(
        'username' => 'str',
    ),
    'get_course_enrolments' => array(
        'course' => 'str',
    ),
    'get_user_enrolments' => array(
        'user' => 'str',
    ),
    'enrol_users' => array(
        'enrolments' => array(array(
            'course' => 'str',
            'user' => 'str',
            'role'  => 'str',
        )),
    ),
   'unenrol_users' => array(
        'enrolments' => array(array(
            'course' => 'str',
            'user' => 'str',
            'role'  => 'str',
        )),
    ),
    'get_groups' => array(
        'course' => 'str',
    ),
    'create_group' => array(
        'course' => 'str',
        'name' => 'str',
        'description' => 'str',
    ),
    'delete_group' => array(
        'course' => 'str',
        'name' => 'str',
    ),
    'get_group_members' => array(
        'course' => 'str',
        'name' => 'str',
    ),
    'add_group_members' => array(
        'course' => 'str',
        'name' => 'str',
        'users' => array(),
    ),
    'remove_group_members' => array(
        'course' => 'str',
        'name' => 'str',
        'users' => array(),
    ),
    'get_user_groups' => array(
        'user' => 'str',
        'course' => 'str',
    ),
    'get_course_grades' => array(
        'course' => 'str',
        'users' => array()
    ),
    'get_user_grades' => array(
        'user' => 'str',
        'courses' => array(),
    ),
    'get_survey_templates' => array(
        'course' => 'str',
    ),
    'create_survey' => array(
        'properties' => array(
            'course' => 'str',
            'section' => 'int',
            'idnumber' => 'str',
            'name' => 'str',
            'summary' => 'str',
            'opendate' => array(
                'year' => 'str?',
                'month' => 'str?',
                'day' => 'str?',
            ),
            'closedate' => array(
                'year' => 'str?',
                'month' => 'str?',
                'day' => 'str?',
            ),
            'template' => array(
                'course' => 'str',
                'idnumber' => 'str',
            ),
        ),
    ),
    'send_mail' => array(
        'message' => array(
            'sender' => 'str',
            'course' => 'str',
            'subject' => 'str',
            'content' => 'str',
            'to' => array(),
            'cc' => array(),
            'bcc' => array(),
        ),
    ),
);

$menu = array(
    array(
        'get_user',
        'create_user',
        'update_user',
        'delete_user',
        'get_user_lastaccess',
    ),
    array(
        'get_course_enrolments',
        'get_user_enrolments',
        'enrol_users',
        'unenrol_users',
    ),
    array(
        'get_groups',
        'create_group',
        'delete_group',
        'get_group_members',
        'add_group_members',
        'remove_group_members',
        'get_user_groups',
    ),
    array(
        'get_course_grades',
        'get_user_grades',
    ),
    array(
        'get_survey_templates',
        'create_survey',
    ),
    array(
        'send_mail',
    ),
);

function get_func() {
    global $functions;
    foreach ($functions as $name => $params) {
        if (isset($_POST["func_$name"])) {
            return $name;
        }
    }
    return isset($_POST['func']) ? $_POST['func'] : 'get_user';
}

function get_moodle() {
    if (isset($_POST['moodle'])) {
        if ($_POST['moodle'] == '1') return 1;
        if ($_POST['moodle'] == '2') return 2;        
        return 0;
    }
    return 1;
}

function get_data($func) {
    global $functions;
    $values = array();
    foreach ($functions[$func] as $name => $type) {
        if ($type === 'str') {
            get_data_str($name, $values, $name, true);
        } else if ($type === 'str?') {
            get_data_str($name, $values, $name, false);
        } else if ($type === array()) {
            get_data_list($name, $values, $name, true);
        } else if (is_array($type) and !isset($type[0])) {
            get_data_dict($name, $type, $values, $name, true);
        } else if (is_array($type) and is_array($type[0])) {
            get_data_list_dict($name, $type[0], $values, $name, true);
        }
    }
    return $values;
}

function get_data_dict($name, $keys, &$data, $data_key, $required=false) {
    $values = array();
    foreach ($keys as $key => $type) {
        if ($type === 'str') {
            get_data_str("$name:$key", $values, $key, true);
        } else if ($type === 'str?') {
            get_data_str("$name:$key", $values, $key, false);
        } else if ($type === 'int') {
            get_data_int("$name:$key", $values, $key, true);
        } else if ($type === 'int?') {
            get_data_int("$name:$key", $values, $key, false);
        } else if ($type === array()) {            
            get_data_list("$name:$key", $values, $key, false);
        } else if (is_array($type) and !isset($type[0])) {
            get_data_dict("$name:$key", $type, $values, $key);
        } else if (is_array($type) and is_array($type[0])) {
            get_data_list_dict("$name:$key", $type[0], $values, $key, false);
        }
    }
    if ($values or $required) {
        $data[$data_key] = $values;
    }
}

function get_data_int($name, &$data, $data_key, $required) {
    if (!empty($_POST["$name:del"])) {
        // skip
    } else if (isset($_POST[$name])) {
        $value = $_POST[$name];
        $data[$data_key] = ($value == (string) (int) $value) ? (int) $value : $value;
    } else if (!empty($_POST["$name:add"]) or $required) {
        $data[$data_key] = '';
    }
}

function get_data_list($name, &$data, $data_key, $required) {
    $values = !empty($_POST[$name]) ? $_POST[$name] : array();
    if (!empty($_POST["$name:del"])) {
        array_splice($values, key($_POST["$name:del"]), 1);
    }
    if (!empty($_POST["$name:add"])) {
        $values[] = '';
    }
    if ($values or $required) {
        $data[$data_key] = $values;
    }
}

function get_data_list_dict($name, $keys, &$data, $data_key, $required) {
    $values = array();
    if (!empty($_POST[$name])) {
        foreach ($_POST[$name] as $index => $value) {
            foreach ($keys as $key => $type) {
                if ($type == 'bool' and isset($value[$key]) and $value[$key] !== '') {
                    $values[$index][$key] = (bool) $value[$key];
                } else if ($type == 'str') {
                    $values[$index][$key] = !empty($value[$key]) ? $value[$key] : '';                    
                }
            }
        }
    }
    if (!empty($_POST["$name:del"])) {
        array_splice($values, key($_POST["$name:del"]), 1);
    }
    if (!empty($_POST["$name:add"])) {
        $value = array();
        foreach ($keys as $key => $type) {
            $value[$key] = '';
        }
        $values[] = $value;
    }
    if ($values or $required) {
        $data[$data_key] = $values;
    }
}

function get_data_str($name, &$data, $data_key, $required) {
    if (!empty($_POST["$name:del"])) {
        // skip
    } else if (isset($_POST[$name])) {
        $data[$data_key] = $_POST[$name];
    } else if (!empty($_POST["$name:add"]) or $required) {
        $data[$data_key] = '';
    }
}

function _print_header() {
    echo '<!DOCTYPE html><html><head>'
        .'<meta charset="utf-8"/>'
        .'<title>Servei web per a secretaria</title>'
        .'<link rel="stylesheet" href="normalize.css" />'
        .'<link rel="stylesheet" href="style.css" />'
        .'</head><body>'
        .'<div class="header"><h1>Servei web per a secretaria</h1>'
        .'</div><div class="content">'
        .'<form method="post">';
}

function _print_footer() {
    echo '</form></div></body></html>';
}

function _print_nav($func, $moodle) {
    global $menu;
    echo '<div class="nav">';
    echo '<input type="submit" name="execute" style="display: none" value="1"/>';
    echo '<input type="hidden" name="func" value="'.$func.'"/>';
    echo '<select name="moodle">'
        .'<option value="1"' . ($moodle == 1 ? ' selected="selected"' : '') . '>Moodle 1.9</option>'
        .'<option value="2"' . ($moodle == 2 ? ' selected="selected"' : '') . '>Moodle 2.x</option>'
        .'<option value="0"' . ($moodle == 0 ? ' selected="selected"' : '') . '>Moodle 1.9 / 2.x</option>'
        .'</select>';
    foreach ($menu as $l) {
        echo '<ul>';
        foreach ($l as $f) {
            echo '<li'.($func == $f ? ' class="current"' : '').'>'
                .'<input type="submit" name="func_'.$f.'" value="'.$f.'"/></li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}


function _print_form($func, $data) {
    global $functions;
    echo '<table>';
    foreach ($functions[$func] as $name => $type) {
        $value = isset($data[$name]) ? $data[$name] : null;
        if ($type === 'str') {
            _print_form_str($name, $name, $value, true);
        } else if ($type === 'str?') {
            _print_form_str($name, $name, $value, false);
        } else if ($type === array()) {
            _print_form_list($name, $name, $value);
        } else if (is_array($type) and !isset($type[0])) {
            _print_form_dict($name, $name, $type, $value);
        } else if (is_array($type) and is_array($type[0])) {
            _print_form_list_dict($name, $name, $type[0], $value);
        }
    }
    echo '</table>';
    echo '<div><input type="submit" class="execute" name="execute" value="Executa"/></div>';
}

function _print_form_dict($label, $name, $keys, $data, $depth=0) {
    echo '<tr class="indent-'.$depth.'">';
    echo '<td><label>'.$label.'</label></td><td></td>';
    echo '</tr>';
    foreach ($keys as $key => $type) {
        $id = "$name:$key";
        $value = isset($data[$key]) ? $data[$key] : null;
        if ($type === 'str' or $type === 'int') {
            _print_form_str($key, $id, $value, true, $depth+1);
        } else if ($type === 'str?' or $type === 'int?') {
            _print_form_str($key, $id, $value, false, $depth+1);
        } else if ($type === array()) {
            _print_form_list($key, $id, $value, $depth+1);
        } else if (is_array($type) and !isset($type[0])) {
            _print_form_dict($key, $id, $type, $value, $depth+1);
        } else if (is_array($type) and is_array($type[0])) {
            _print_form_list_dict($key, $id, $type[0], $value, $depth+1);
        }
    }
}

function _print_form_str($label, $name, $data, $required, $depth=0) {
    echo '<tr class="indent-'.$depth.'">';
    $for = ($required or $data !== null) ? ' for="'.$name.'"' : '';
    echo '<td><label'.$for.'>'.$label.'</label></td>';
    if ($required) {
        echo '<td><input type="text" id="'.$name.'" name="'.$name.'" value="'.$data.'" /></td>';
    } else if ($data !== null) {
        echo '<td><input type="text" id="'.$name.'" name="'.$name.'" value="'.$data.'" />';
        echo '<input type="submit" name="'.$name.':del" value="Suprimeix" /></td>';
    } else {
        echo '<td><input type="submit" name="'.$name.':add" value="Afegeix"/></td>';
    }
    echo '</tr>';
}

function _print_form_list($label, $name, $data, $depth=0) {
    echo '<tr class="indent-'.$depth.'">';
    echo '<td><label>'.$label.'</label></td>';
    echo '<td>';
    if (!empty($data)) {
        foreach ($data as $index => $value) {
            echo '<input type="text" name="'.$name.'['.$index.']" value="'.$value.'" />';
            echo '<input type="submit" name="'.$name.':del['.$index.']" value="Suprimeix" />';
            echo '<br/>';
        }
    }
    echo '<input type="submit" name="'.$name.':add" value="Afegeix"/>';
    echo '</td></tr>';
}

function _print_form_list_dict($label, $name, $keys, $data, $depth=0) {
    echo '<tr class="indent-'.$depth.'">';
    echo '<td><label>'.$label.'</label></td><td></td>';
    echo '</tr></table><table>';
    if ($data) {
        echo '<tr class="indent-'.($depth+1).'">';
        foreach ($keys as $key => $type) {
            echo '<th><label for="'.$name.'[0]['.$key.']">'.$key.'</label></th>';
            $last_key = $key;
        }
        echo '<th></th>';
        echo '</tr>';
        foreach ($data as $index => $value) {
            echo '<tr class="indent-'.($depth+1).'"><td>';
            foreach ($keys as $key => $type) {
                if ($type == 'bool') {
                    $option = (isset($value[$key]) and $value[$key] !== '') ? (bool) $value[$key] : '';
                    echo '<select name="'.$name.'['.$index.']['.$key.']" />';
                    echo '<option value=""'.($option === '' ?  ' selected="selected"' : '').'></option>';
                    echo '<option value="1"'.($option === true ?  ' selected="selected"' : '').'>true</option>';
                    echo '<option value="0"'.($option === false ?  ' selected="selected"' : '').'>false</option>';
                } elseif ($type == 'str') {
                    echo '<input type="text" name="'.$name.'['.$index.']['.$key.']" value="'.$value[$key].'" />';
                }
                echo '</td><td>';
            }
            echo '<input type="submit" name="'.$name.':del['.$index.']" value="Suprimeix" /></td></tr>';
        }
    }
    echo '<tr class="indent-'.($depth+1).'">';
    echo '<td><input type="submit" name="'.$name.':add" value="Afegeix"/></td><td></td>';
    echo '</tr></table><table>';
}

function _print_output($func, $data, $result) {
    echo '<div class="output"><pre><span class="format_var">$moodle</span>->';
    _print_call('<span class="format_func">'.$func.'</span>', $data, 0, true);
    echo ';</pre></div>';
    if ($result instanceof MoodleException) {
        echo '<div class="output error">' . $result->getMessage() . '</div>';
    } else {
        echo '<div class="output">';
        var_dump($result);
        echo '</div>';
    }
}

function _print_call($name, $params, $depth, $forcelist=false) {
    $fields = array();
    $n_arrays = 0;
    $last_array = false;
    $is_list = true;
    foreach ($params as $key => $value) {        
        if (is_numeric($key) or $forcelist) $key = null;
        $fields[] = array($key, $value);
        if ($last_array = is_array($value)) $n_arrays++;
        if ($key) $is_list = false;
    }

    $complex = ($n_arrays > 1 or
                $n_arrays == 1 and !$last_array or
                !$is_list and count($fields) > 3);

    if ($complex) {
        $start = "\n" . str_repeat('    ', $depth+1);
        $middle = ",\n" . str_repeat('    ', $depth+1);
        $end = ",\n" . str_repeat('    ', $depth);
    } else {
        $start = '';
        $middle = ', ';
        $end = '';
        $depth -= 1;
    }

    $count = count($params);
    $index = 0;

    echo "$name(";
    foreach ($fields as $field) {
        list($key, $value) = $field;
        echo ($index == 0 ? $start : $middle);
        if ($key) {
            _print_param($key);
            echo ' => ';
        }
        _print_param($value, $depth+1);
        if ($index == $count-1) echo $end;
        $index++;
    }
    echo ')';
}

function _print_param($param, $depth=0) {
    if (is_string($param)) {
        echo '<span class="format_str">\''.$param.'\'</span>';
    } else if (is_int($param)) {        
        echo '<span class="format_int">'.$param.'</span>';
    } else if (is_bool($param)) {
        echo '<span class="format_bool">'.($param ? 'true' : 'false').'</span>';
    } else if (is_array($param)) {
        _print_call('array', $param, $depth);
    }
}

$func = get_func();
$data = get_data($func);
$moodle = get_moodle();
_print_header();
_print_nav($func, $moodle);
echo '<div class="main"><h2>'.$func.'</h2>';
_print_form($func, $data);
if (!empty($_POST['execute'])) {
    $moodle = new Moodle($config, $moodle);
    try {
        $result = call_user_func_array(array($moodle, $func), $data);
    } catch (MoodleException $e) {
        $result = $e;
    }
    _print_output($func, $data, $result);
}
echo '</div>';
_print_footer();
