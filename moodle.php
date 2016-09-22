<?php

class Moodle {

    private $config;
    private static $functions = array(
        'get_user' => array('username'),
        'get_user_lastaccess' => array('users'),
        'create_user' => array('properties'),
        'update_user' => array('username', 'properties'),
        'delete_user' => array('username'),
        'get_users' => array('usernames'),
        'has_course' => array('course'),
        'get_course' => array('course'),
        'update_course' => array('course', 'properties'),
        'get_courses' => array(),
        'get_course_enrolments' => array('course'),
        'get_user_enrolments' => array('user'),
        'enrol_users' => array('enrolments'),
        'unenrol_users' => array('enrolments'),
        'get_groups' => array('course'),
        'create_group' => array('course', 'name', 'description'),
        'delete_group' => array('course', 'name'),
        'get_group_members' => array('course', 'name'),
        'add_group_members' => array('course', 'name', 'users'),
        'remove_group_members' => array('course', 'name', 'users'),
        'get_user_groups' => array('user', 'course'),
        'get_course_grades' => array('course', 'users'),
        'get_user_grades' => array('user', 'courses'),
        'get_assignments' => array('course'),
        'get_assignment_submissions' => array('course', 'idnumber'),
        'get_forum_stats' => array('course'),
        'get_forum_user_stats' => array('course', 'users'),
        'get_surveys' => array('course'),
        'get_surveys_data' => array('course'),
        'create_survey' => array('properties'),
        'update_survey' => array('course', 'idnumber', 'properties'),
        'send_mail' => array('message'),
        'get_mail_stats' => array('user', 'starttime', 'endtime'),
        'calc_formula' => array('formula', 'variables', 'values'),
        'get_course_url' => array('course'),
        'reset_password' => array('username'),
    );

    function __construct($config) {
        $this->config = $config;
    }

    function __call($name, $arguments) {
        if (!isset(self::$functions[$name])) {
            throw new MoodleException('Unknown function');
        } elseif (count($arguments) != count(self::$functions[$name])) {
            throw new MoodleException('Invalid parameters');
        } elseif (!empty(self::$functions[$name])) {
            $arguments = array_combine(self::$functions[$name], $arguments);
        }

        return $this->call_moodle($name, $arguments);
    }

    private function call_moodle($func, $params=array()) {
        $url = "https://{$this->config->server}/webservice/rest/server.php"
            . "?wstoken={$this->config->token}&wsfunction=secretaria_{$func}"
            . "&moodlewsrestformat=json";
        $response = self::curl($url, self::format_params($params));
        $response = json_decode($response, true);
        if (isset($response['exception'])) {
            throw new MoodleException($response['message']);
        }
        return $response;
    }

    private static function curl($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3600);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new MoodleException($error);
        }
        return $response;
    }

    private static function format_params(array $params, $prefix='') {
        $result = array();
        foreach ($params as $key => $value) {
            $key = $prefix ? $prefix.'['.urlencode($key).']' : urlencode($key);
            if (is_array($value)) {
                $result[] = self::format_params($value, $key);
            } else if (is_bool($value)) {
                $result[] = $key.'='.((int) $value);
            } else {
                $result[] = $key.'='.urlencode($value);
            }
        }
        return implode('&', $result);
    }

    private static function validate_email($address) {
        return preg_match('#^[-!\#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+'.
                          '(\.[-!\#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+)*'.
                          '@'.
                           '[-!\#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
                          '[-!\#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$#',
                          $address);
    }
}

class MoodleException extends Exception {
}
