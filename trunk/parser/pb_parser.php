<?php
/**
 * Parse a .proto file and generates the classes in a file
 * @author Nikolai Kordulla
 */
class PBParser
{
    var $m_pathname = '';

    // the message types array of (field, param[]='repeated,required,optional')
    var $m_types = array();

    // different types
    var $scalar_types = array('double', 'float', 'int32' => 'PBInt', 'int64' => 'PBInt',
                              'uint32' => 'PBInt', 'uint64' => 'PBInt', 'sint32' => 'PBSignedInt', 'sint64' => 'PBSignedInt',
                              'fixed32', 'fixed64', 'sfixed32', 'sfixed64',
                              'bool' => 'PBBool', 'string' => 'PBString', 'bytes' => 'PBBytes');

    /**
     * parses the profile and generates a filename with the name
     * pb_proto_[NAME]
     * @param String $protofile - the protofilename with the path
     */
    public function parse($protofile)
    {
        $this->m_types = array();

        $this->m_pathname = dirname(realpath($protofile));
        $name = $this->_get_proto_name($protofile);
        $string = file_get_contents($protofile);
        $this->_parse($name, $string);

        // now create file with classes
        $this->_create_class_file($name, 'pb_proto_'.$name.'.php');
    }

    private function _get_proto_name($protofile)
    {
        // now take the filename
        $filename = str_replace("\\", "/", $protofile);
        $filename = split("/", $filename);
        $filename = $filename[count($filename) - 1];
        $name = split('\.', $filename);
        array_pop($name);
        $name = implode('.', $name);

        return $name;
    }

    private function _parse($scope, $string)
    {
        $package = '';

        $this->_strip_comments($string);
        $string = trim($string);

        while (strlen($string) > 0)
        {
            $next = ($this->_next($string));
            if (strtolower($next) == 'package')
            {
                $string = trim(substr($string, strlen($next)));
                $next = $this->_next($string);
                $package = trim($next, ';');
                $string = trim(substr($string, strlen($next)));
            }
            else if (strtolower($next) == 'import')
            {
                $string = trim(substr($string, strlen($next)));
                $next = $this->_next($string);
                $protofile = trim($next, ';"');
                $protofile = $this->m_pathname.'/'.$protofile;
                $this->_parse($this->_get_proto_name($protofile), file_get_contents($protofile));
                $string = trim(substr($string, strlen($next)));
            }
            else
            {
                $this->_parse_message($scope, $package, $string, '');
            }
        }
    }

    /**
     * Creates php class file for the proto file
     *
     * @param String $filename - the filename of the php file
     */
    private function _create_class_file($scope, $filename)
    {
        $string = '';
        foreach ($this->m_types as $classfile)
        {
            if ($classfile['scope'] != $scope)
                continue;

            $classname = str_replace(".", "_", $classfile['name']);

            if ($classfile['type'] == 'message')
            {
                $string .= 'class '.$classname." extends PBMessage\n{\n";
                $this->_create_class_constructor($classfile['value'], $string, $classname);
                $this->_create_class_body($classfile['value'], $string, $classname);
            }
            else if ($classfile['type'] == 'enum')
            {
                $string .= 'class '.$classname." extends PBEnum\n{\n";
                $this->_create_class_definition($classfile['value'], $string);
            }

            // now create the class body with all set and get functions

            $string .= "}\n";
        }
        file_put_contents($filename, '<?php'."\n".$string.'?>');
    }

    /**
     * Creates the class body with functions for each field
     * @param Array $classfile
     * @param String $string
     * @param String $classname - classname
     */
    private function _create_class_body($classfile, &$string, $classname)
    {
        foreach($classfile as $field)
        {
            if (isset($field['value']['repeated']) && isset($this->scalar_types[$field['value']['type']]) )
            {
                $string .= '    function '.$field['value']['name'].'()'."\n";
                $string .= "    {\n";
                $string .= '        $arr = array();'."\n";
                $string .= '        for ($i = 0; $i < $this->_get_arr_size('.$field['value']['value'].'); ++$i)'."\n";
                $string .= "        {\n";
                $string .= '            $arr[] = $this->_get_arr_value('.$field['value']['value'].', $i)->get_value();'."\n";
                $string .= "        }\n";
                $string .= '        return $arr;'."\n";
                $string .= "    }\n";

                $string .= '    function add_'.$field['value']['name'].'($value)'."\n";
                $string .= "    {\n";
                $string .= '        $v = new '.$this->scalar_types[$field['value']['type']].'();'."\n";
                $string .= '        $v->set_value($value);'."\n";
                $string .= '        $this->values['.$field['value']['value'].'][] = $v;'."\n";
                $string .= "    }\n";

                $string .= '    function set_'.$field['value']['name'].'($arr_value)'."\n";
                $string .= "    {\n";
                $string .= '        $this->values['.$field['value']['value'].'] = array();'."\n";
                $string .= '        foreach ($arr_value as $value)'."\n";
                $string .= "        {\n";
                $string .= '            $v = new '.$this->scalar_types[$field['value']['type']].'();'."\n";
                $string .= '            $v->set_value($value);'."\n";
                $string .= '            $this->values['.$field['value']['value'].'][] = $v;'."\n";
                $string .= "        }\n";
                $string .= "    }\n";

                $string .= '    function clear_'.$field['value']['name'].'()'."\n";
                $string .= "    {\n";
                $string .= '        $this->values['.$field['value']['value'].'] = array();'."\n";
                $string .= "    }\n";
            }           
            else if (isset($field['value']['repeated']))
            {
                $type = $this->m_types[$field['value']['namespace']]['type'];

                if ($type == 'message')
                {
                    $string .= '    function '.$field['value']['name'].'()'."\n";
                    $string .= "    {\n";
                    $string .= '        return $this->values['.$field['value']['value'].'];'."\n";
                    $string .= "    }\n";

                    $string .= '    function add_'.$field['value']['name'].'($value)'."\n";
                    $string .= "    {\n";
                    $string .= '        $this->values['.$field['value']['value'].'][] = $value;'."\n";
                    $string .= "    }\n";

                    $string .= '    function set_'.$field['value']['name'].'($arr_value)'."\n";
                    $string .= "    {\n";
                    $string .= '        $this->values['.$field['value']['value'].'] = $arr_value;'."\n";
                    $string .= "    }\n";

                    $string .= '    function clear_'.$field['value']['name'].'()'."\n";
                    $string .= "    {\n";
                    $string .= '        $this->values['.$field['value']['value'].'] = array();'."\n";
                    $string .= "    }\n";
                }
                else if ($type == 'enum')
                {
                    $string .= '    function '.$field['value']['name'].'()'."\n";
                    $string .= "    {\n";
                    $string .= '        $arr = array();'."\n";
                    $string .= '        for ($i = 0; $i < $this->_get_arr_size('.$field['value']['value'].'); ++$i)'."\n";
                    $string .= "        {\n";
                    $string .= '            $arr[] = $this->_get_arr_value('.$field['value']['value'].', $i)->get_value();'."\n";
                    $string .= "        }\n";
                    $string .= '        return $arr;'."\n";
                    $string .= "    }\n";

                    $string .= '    function add_'.$field['value']['name'].'($value)'."\n";
                    $string .= "    {\n";
                    $string .= '        $v = $this->_add_arr_value('.$field['value']['value'].');'."\n";
                    $string .= '        $v->set_value($value);'."\n";
                    $string .= "    }\n";

                    $string .= '    function set_'.$field['value']['name'].'($arr_value)'."\n";
                    $string .= "    {\n";
                    $string .= '        $this->values['.$field['value']['value'].'] = array();'."\n";
                    $string .= '        foreach($arr_value as $value)'."\n";
                    $string .= "        {\n";
                    $string .= '            $v = $this->_add_arr_value('.$field['value']['value'].');'."\n";
                    $string .= '            $v->set_value($value);'."\n";
                    $string .= "        }\n";
                    $string .= "    }\n";

                    $string .= '    function clear_'.$field['value']['name'].'()'."\n";
                    $string .= "    {\n";
                    $string .= '        $this->values['.$field['value']['value'].'] = array();'."\n";
                    $string .= "    }\n";
                }
            }
            else
            {
                $string .= '    function '.$field['value']['name']."()\n";
                $string .= "    {\n";
                $string .= '        return $this->_get_value('.$field['value']['value'].');'."\n";
                $string .= "    }\n";

                $string .= '    function set_'.$field['value']['name'].'($value)'."\n";
                $string .= "    {\n";
                $string .= '        return $this->_set_value('.$field['value']['value'].', $value);'."\n";
                $string .= "    }\n";
            }
        }
    }

    /**
     * Creates the class definitions
     * @param Array $classfile
     * @param String $string
     */
    private function _create_class_definition($classfile, &$string)
    {
        foreach($classfile as $field)
        {
            $string .= '    const '.$field['0'].' = '.$field['1'].";\n";
        }

    }

    /**
     * Creates the class constructor
     * @param Array $classfile
     * @param String $string
     * @param String $classname - classname
     */
    private function _create_class_constructor($classfile, &$string, $classname)
    {
        $string .= '    var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;'."\n";
        $string .= "    public function __construct(".'$reader = null'.")\n";
        $string .= "    {\n";
        $string .= "        parent::__construct(".'$reader'.");\n";

        foreach($classfile as $field)
        {
            $classtype = "";
            // scalar types don't have namespace definition
            $classtype = empty($field['value']['namespace']) ? $field['value']['type'] : $field['value']['namespace'];
            $classtype = str_replace(".", "_", $classtype);
            $_classtype = $classtype;
            // create the right namespace
            if (isset($this->scalar_types[strtolower($classtype)]))
            {
                $classtype = $this->scalar_types[$classtype];
            }
            else if ((strpos($classtype, '_') === false))
            {
                $classtype = str_replace('.', '_', $field['value']['namespace']);
            }

            $string .= '        $this->fields['.$field['value']['value'].'] = \''.$classtype.'\''.";\n";

            if (isset($field['value']['repeated']))
            {
                $string .= '        $this->values['.$field['value']['value'].'] = array()'.";\n";
            }
            else
            {
                $string .= '        $this->values['.$field['value']['value'].'] = \'\''.";\n";
            }

            // default value only for optional fields
            if (!isset($field['value']['repeated']) && isset($field['value']['optional'])
                    && isset($field['value']['default']))
            {
                $string .= '        $this->values['.$field['value']['value'].'] = new '.$classtype."();\n";
                if (isset($this->scalar_types[strtolower($_classtype)]))
                {
                    $string .= '        $this->values['.$field['value']['value'].']->value = '.$field['value']['default'].";\n";
                // it must be an enum field perhaps type check
                }
                else
                {
                    $string .= '        $this->values['.$field['value']['value'].']->value = '.$classtype.'::'.$field['value']['default'].";\n";
                }
            }
        }
        $string .= "    }\n";
    }

    /**
     * Parses the message
     * @param String $string the proton file as string
     */
    private function _parse_message($scope, $package, &$string, $m_name, $path = '')
    {
        $myarray = array();

        $string = trim($string);
        while (strlen($string) > 0)
        {
            $next = ($this->_next($string));
            if (strtolower($next) == 'message')
            {
                $string = trim(substr($string, strlen($next)));
                $name = $this->_next($string);
                $offset = $this->_get_begin_end($string, "{", "}");
                $content = trim(substr($string, $offset['begin'] + 1, $offset['end'] - $offset['begin'] - 2));
                $namespace = trim($package.'.'.trim($path.'.'.$name, '.'), '.');
                $this->m_types[$namespace] = array(
                    'scope' => $scope,
                    'name' => $namespace,
                    'type' => 'message',
                    'value' => $this->_parse_message($scope, $package, $content, $name, trim($path . '.' . $name, '.')));
                // removing it from string
                $string = '' . trim(substr($string, $offset['end']));
            }
            else if (strtolower($next) == 'enum')
            {
                $string = trim(substr($string, strlen($next)));
                $name = $this->_next($string);
                $offset = $this->_get_begin_end($string, "{", "}");
                $content = trim(substr($string, $offset['begin'] + 1, $offset['end'] - $offset['begin'] - 2));
                $namespace = trim($package.'.'.trim($path.'.'.$name, '.'), '.');
                $this->m_types[$namespace] = array(
                    'scope' => $scope,
                    'name' => $namespace,
                    'type' => 'enum',
                    'value' => $this->_parse_enum($content));
                // removing it from string
                $string = '' . trim(substr($string, $offset['end']));
            }
            else
            {
                // now a normal field
                $match = preg_match('/(.*);\s?/', $string, $matches, PREG_OFFSET_CAPTURE);
                if (!$match)
                    throw new Exception('Proto file missformed');
                $myarray[] = array('type' => 'field', 'value' => $this->_parse_field($scope, $package, $matches[0][0], $myarray, $path));
                $string = trim(substr($string, $matches[0][1] + strlen($matches[0][0])));
            }
        }

        return $myarray;
    }

    /**
     * Parses a normal field
     * @param String $content - content
     */
    private function _parse_field($scope, $package, $content, $array, $path)
    {
        $myarray = array();

        // parse the default value
        $match = preg_match('/\[\s?default\s?=\s?([^\[]*)\]\s?;/', $content, $matches, PREG_OFFSET_CAPTURE);
        if ($match)
        {
            $myarray['default'] = $matches[1][0];
            $content = trim(substr($content, 0, $matches[0][1])) . ';';
        }

        // parse the value
        $match = preg_match('/=\s(.*);/', $content, $matches, PREG_OFFSET_CAPTURE);
        if ($match)
        {
            $myarray['value'] = trim($matches[1][0]);
            $content = trim(substr($content, 0, $matches[0][1]));
        }
        else
            throw new Exception('Protofile no value at ' . $content);

        // parse all modifier
        $content = trim(trim(trim($content), ';'));
        $typeset = false;
        while (strlen($content) > 0)
        {
            $matches = $this->_next($content, true);
            $name = $matches[0][0];
            if (strtolower($name) == 'optional')
            {
                $myarray['optional'] = true;
            }
            else if (strtolower($name) == 'required')
            {
                $myarray['required'] = true;
            }
            else if (strtolower($name) == 'repeated')
            {
                $myarray['repeated'] = true;
            }
            else if ($typeset == false)
            {
                $type = $this->_check_type($scope, $package, $name, $path);
                $myarray['type'] = $type[0];
                $myarray['namespace'] = $type[1];
                $typeset = true;
            }
            else
            {
                $myarray['name'] = $name;
            }
            $content = trim(substr($content, strlen($name)));
        }

        return $myarray;
    }


    /**
     * Checks if a type exists
     * @param String $type - the type
     */
    private function _check_type($scope, $package, $type, $path)
    {
        // check scalar types
        $namespace = '';
        if (isset($this->scalar_types[strtolower($type)]))
        {
            // scalar types don't have namespace definition
            //printf("[1] scope=%s, package=%s, type=%s, namespace=%s\n", $scope, $package, $type, $namespace);
            return array(strtolower($type), $namespace);
        }

        // try to find type from the message types array
        foreach ($this->m_types as $message)
        {
            $namespace = $type;
            if ($message['name'] == $namespace)
            {
                if (strcmp($scope, $message['scope']) == 0)
                {
                    $type = trim($package.'.'.$type, '.');
                    $namespace = trim($package.'.'.$namespace, '.');
                }
                //printf("[2] proto=%s, package=%s, scope=%s, path=%s, type=%s, namespace=%s\n", $scope, $package, $message['scope'], $path, $type, $namespace);
                return array($type, $namespace);
            }

            $namespace = trim($package.'.'.trim($path.'.'.$type, '.'), '.');
            if ($message['name'] == $namespace)
            {
                //printf("[3] proto=%s, package=%s, scope=%s, path=%s, type=%s, namespace=%s\n", $scope, $package, $message['scope'], $path, $type, $namespace);
                return array($type, $namespace);
            }
        }

        // the type may be defined in the other location
        $type = trim($package.'.'.$type, '.');
        $namespace = $type;
        //printf("[4] proto=%s, package=%s, scope=%s, path=%s, type=%s, namespace=%s\n", $scope, $package, $message['scope'], $path, $type, $namespace);

        return array($type, $namespace);
    }

    /**
     * Parses enum
     * @param String $content content of the enum
     */
    private function _parse_enum($content)
    {
        $myarray = array();
        $match = preg_match_all('/(.*);\s?/', $content, $matches);
        if (!$match)
            throw new Execption('Semantic error in Enum!');
        foreach ($matches[1] as $match)
        {
            $split = split("=", $match);
            $myarray[] = array(trim($split[0]), trim($split[1]));
        }
        return $myarray;
    }

    /**
     * Gets the next String
     */
    private function _next($string, $reg = false)
    {
        $match = preg_match('/([^\s^\{}]*)/', $string, $matches, PREG_OFFSET_CAPTURE);
        if (!$match)
            return -1;
        if (!$reg)
            return (trim($matches[0][0]));
        else
            return $matches;
    }

    /**
     * Returns the begin and endpos of the char
     * @param String $string protofile as string
     * @param String $char begin element such as '{'
     * @param String $charend end element such as '}'
     * @return array begin, end
     */
    private function _get_begin_end($string, $char, $charend)
    {
        $offset_begin = strpos($string, $char);

        if ($offset_begin === false)
            return array('begin' => -1, 'end' => -1);

        $_offset_number = 1;
        $_offset = $offset_begin + 1;
        while ($_offset_number > 0 && $_offset > 0)
        {
            // now search after the end nested { }
            $offset_open = strpos($string, $char, $_offset);
            $offset_close = strpos($string, $charend, $_offset);
            if ($offset_open < $offset_close && !($offset_open === false))
            {
                $_offset = $offset_open+1;
                $_offset_number++;
            }
            else if (!($offset_close === false))
            {
                $_offset = $offset_close+1;
                $_offset_number--;
            }
            else
                $_offset = -1;
        }

        if ($_offset == -1)
            throw new Exception('Protofile failure: ' . $char . ' not nested');

        return array('begin' => $offset_begin, 'end' => $_offset);
    }

    /**
     * Strips the comments out
     * @param String $string the proton file as string
     */
    private function _strip_comments(&$string)
    {
        $string = preg_replace('/\/\/.+/', '', $string);
        // now replace empty lines and whitespaces in front
        $string = preg_replace('/\\r?\\n\s*/', "\n", $string);
    }
}
?>
