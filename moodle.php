<?php

class Moodle {

    private $config;
    private $moodle;
    private static $functions = array(
        'get_user' => array('username'),
        'get_user_lastaccess' => array('users'),
        'create_user' => array('properties'),
        'update_user' => array('username', 'properties'),
        'delete_user' => array('username'),
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
        'get_surveys' => array('course'),
        'create_survey' => array('properties'),
        'send_mail' => array('message'),
        'get_courses' => array(),
    );

    function __construct($config, $moodle=false) {
        $this->config = $config;
        $this->moodle = $moodle;
    }

    function __call($name, $arguments) {
        if (!isset(self::$functions[$name])) {
            throw new MoodleException('Unknown function');
        } elseif (count($arguments) != count(self::$functions[$name])) {
            throw new MoodleException('Invalid parameters');
        } else {
            $arguments = array_combine(self::$functions[$name], $arguments);
        }

        if ($this->moodle == '1') {
            return $this->call_1($name, $arguments);
        } else if ($this->moodle == '2') {
            return $this->call_2($name, $arguments);
        }

        switch ($name) {

        case 'get_user':
            $user1 = $this->call_1($name, $arguments);
            $user2 = $this->call_2($name, $arguments);
            $user1['lastaccess'] = max($user1['lastaccess'], $user2['lastaccess']);
            return $user1;

        case 'create_user':
            $this->call_1('create_user', $arguments);
            try {
                $this->call_2('create_user', $arguments);
            } catch (MoodleException $e) {
                if (!empty($arguments['properties']['username'])) {
                    $username = $arguments['properties']['username'];
                    $this->call_1('delete_user', array('username' => $username));
                }
                throw $e;
            }
            break;

        case 'update_user':
        case 'delete_user':
            $this->call_1($name, $arguments);
            $this->call_2($name, $arguments);
            break;

        case 'get_course_enrolments':
        case 'get_groups':
        case 'create_group':
        case 'delete_group':
        case 'get_group_members':
        case 'add_group_members':
        case 'remove_group_members':
        case 'get_user_groups':
        case 'get_course_grades':
        case 'get_surveys':
            if ($this->has_course_2($arguments['course'])) {
                return $this->call_2($name, $arguments);
            } else {
                return $this->call_1($name, $arguments);
            }

        case 'get_user_enrolments':
        case 'get_user_lastaccess':
            $inmoodle2 = array_flip($this->call_2('get_courses'));
            $result1 = array();
            foreach ($this->call_1($name, $arguments) as $access) {
                if (!isset($inmoodle[$access['course']])) {
                    $result1[] = $access;
                }
            }
            $result2 = $this->call_2($name, $arguments);
            return array_merge($result1, $result2);

        case 'enrol_users':
        case 'unenrol_users':
            $inmoodle2 = array_flip($this->call_2('get_courses'));
            $enrolments1 = array();
            $enrolments2 = array();
            foreach ($arguments['enrolments'] as $enrolment) {
                if (isset($inmoodle2[$enrolment['course']])) {
                    $enrolments2[] = $enrolment;
                } else {
                    $enrolments1[] = $enrolment;
                }
            }
            $this->call_1($name, array('enrolments' => $enrolments1));
            $this->call_2($name, array('enrolments' => $enrolments2));
            break;

        case 'get_user_grades':
            $inmoodle2 = array_flip($this->call_2('get_courses'));
            $courses1 = array();
            $courses2 = array();
            foreach ($arguments['courses'] as $course) {
                if (isset($inmoodle2[$course])) {
                    $courses2[] = $course;
                } else {
                    $courses1[] = $course;
                }
            }
            $result1 = $this->call_1($name, array(
                'user' => $arguments['user'],
                'courses' => $courses1,
            ));
            $result2 = $this->call_2($name, array(
                'user' => $arguments['user'],
                'courses' => $courses2,
            ));
            return array_merge($result1, $result2);

        case 'send_mail':
        case 'create_survey':
            $properties = reset($arguments);
            $course = isset($properties['course']) ? $properties['course'] : '';
            if ($this->has_course_2($course)) {
                return $this->call_2($name, $arguments);
            } else {
                return $this->call_1($name, $arguments);
            }
            break;

        case 'get_courses':
            $result1 = $this->call_1($name, $arguments);
            $result2 = $this->call_2($name, $arguments);
            return array_unique(array_merge($result1, $result2));
        }
    }

    private function call_1($func, $params=array()) {
        $url = "https://{$this->config->server1}/local/secretaria/webservice.php";
        $data = array('token' => $this->config->token1,
                      'func' => $func, 'params' => $params);
        $response = self::curl($url, 'data=' . urlencode(json_encode($data)));
        $response = json_decode($response, true);
        if ($response['error']) {
            throw new MoodleException($response['error']);
        }
        return $response['result'];
    }

    private function call_2($func, $params=array()) {
        $url = "https://{$this->config->server2}/webservice/rest/server.php"
            . "?wstoken={$this->config->token2}&wsfunction=secretaria_{$func}"
            . "&moodlewsrestformat=json";
        $response = self::curl($url, self::format_params_2($params));
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

    private static function format_params_2(array $params, $prefix='') {
        $result = array();
        foreach ($params as $key => $value) {
            $key = $prefix ? $prefix.'['.urlencode($key).']' : urlencode($key);
            if (is_array($value)) {
                $result[] = self::format_params_2($value, $key);
            } else if (is_bool($value)) {
                $result[] = $key.'='.((int) $value);
            } else {
                $result[] = $key.'='.urlencode($value);
            }
        }
        return implode('&', $result);
    }

    private function has_course_2($course) {
        return $this->call_2('has_course', array('course' => $course));
    }
}

class MoodleException extends Exception {
}
