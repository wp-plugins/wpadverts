<?php
/**
 * Form Class
 * 
 * This class is used to handle all Adverts form needs: rendering, validation
 * and filtering.
 * 
 * @author Grzegorz Winiarski
 * @since 1.0
 * @package Adverts
 * @subpackage Classes
 * @since 0.1
 */

class Adverts_Form
{
    protected $_scheme = NULL;
    
    protected $_form = array();
    
    /**
     * Constructs form object
     * 
     * @param mixed $form (array or null)
     */
    public function __construct( $form = NULL ) {
        if( $form ) {
            $this->load($form);
        }
    }
    
    /**
     * Get filter by name from registered validators array
     * 
     * @param string $name
     * @return mixed (array or null)
     */
    public function get_filter( $name ) {
        $filters = Adverts::instance()->get("field_filter", array());
        
        if(isset($filters[$name])) {
            return $filters[$name];
        } else {
            return null;
        }   
    }
    
    /**
     * Get validator by name from registered validators array
     * 
     * @param string $name
     * @return mixed (array or null)
     */
    public function get_validator( $name ) {
        $validators = Adverts::instance()->get("field_validator", array());
        
        if(isset($validators[$name])) {
            return $validators[$name];
        } else {
            return null;
        }
    }
    
    /**
     * Loads form scheme from array
     * 
     * @param array $form
     */
    public function load( $form ) {
        $this->_scheme = apply_filters("adverts_form_load", $form);
    }
    
    /**
     * Set fields values
     * 
     * @param array $data
     */
    public function bind( $data ) {
        
        $fields = Adverts::instance()->get("form_field");
        
        foreach($this->_scheme["field"] as $field) {
            
            $key = $field["name"];
            $type = $field["type"];
            
            if( isset( $data[$key] ) ) {
                $value = $data[$key];
            } else {
                $value = null;
            }
            
            if( !isset($this->_form[$key]) ) {
                $this->_form[$key] = array();
            }

            if( isset($fields[$type]) && is_callable( $fields[$type]["callback_bind"] ) ) {
                $this->_form[$key]["value"] = call_user_func($fields[$type]["callback_bind"], $field, $value );
            }
            
        }
        
        do_action( "adverts_form_bind", $this, $data );
    }
    
    /**
     * Validates Form
     * 
     * Checks if submitted form data is valid, before using this function make sure 
     * to bind {@see self::bind()} data first.
     * 
     * @since 1.0
     * @return boolean
     */
    public function validate() {
        
        $valid = true;
        
        foreach($this->_scheme["field"] as $field) {
            if( !isset($field["validator"]) ) {
                continue;
            }
            
            $name = $field["name"];
            
            foreach( $field["validator"] as $v ) {
                
                $v = array_merge($this->get_validator( $v["name"] ), $v);
                
                if(empty($this->_form[$name]["value"]) && $v["validate_empty"] === false ) {
                    continue;
                } 
                
                $value = $this->_form[$name]["value"];
                if(empty($value) && !is_numeric($value)) {
                    $params = array_merge(array(null), $v["params"]);
                } else {
                    $params = array_merge((array)$value, $v["params"]);
                }
                
                $result = call_user_func_array( $v["callback"], (array)$params );
                
                if( $result === true || $result === 1) {
                    continue;
                }
                
                $valid = false;
                
                if( !isset($this->_form[$name]["error"]) || !is_array($this->_form[$name]["error"]) ) {
                    $this->_form[$name]["error"] = array();
                }

                if( isset($v["message"][$result]) ) {
                    $this->_form[$name]["error"][] = $v["message"][$result];
                } elseif( isset($v["default_error"]) ) {
                   $this->_form[$name]["error"][] = $v["default_error"]; 
                } else {
                    $this->_form[$name]["error"][] = __( "Invalid value.", "adverts" );
                }
                
                if( isset($v["on_failure"]) && $v["on_failure"] == "break" ) {
                    break;
                }
                
                
            }
            
        }
        
        return $valid;
    }
    
    /**
     * Returns all or filtered array of form fields.
     * 
     * Options
     * - exclude: array of fields to exclude
     * - type: (default !adverts_field_hidden) show or hide fields based on type
     * 
     * @param array $options
     * @return array
     */
    public function get_fields( $options = array() ) {
        $fields = array();
        $data = $this->_form;

        if(isset($options["exclude"]) && is_array($options["exclude"])) {
            $exclude = $options["exclude"];
        } else {
            $exclude = array();
        }
        
        if(isset($options["type"]) && is_array($options["type"])) {
            $type_include = array();
            $type_exclude = array();
            
            foreach($options["type"] as $type) {
                if(stripos($type, "!") === 0) {
                    $type_exclude[] = substr($type, 1);
                } else {
                    $type_include[] = $type;
                }
            }
            
        } else {
            $type_include = array();
            $type_exclude = array( 'adverts_field_hidden' );
        }

        foreach($this->_scheme["field"] as $field) {
            if( in_array($field["name"], $exclude) ) {
                continue;
            }
            
            if(in_array($field["type"], $type_exclude)) {
                continue;
            }
            
            if( !empty($type_include) ) {
                if(in_array($field["type"], $type_include)) {
                    $fields[] = $field;
                }

                continue;
            }
            
            $fields[] = $field;
        }
        
        foreach($fields as $k => $field) {
            $name = $field["name"];
            if(isset($data[$name])) {
                $fields[$k] = array_merge( $field, $data[$name] );
            }
        }
        
        array_walk( $fields, array( $this, "_decorate" ) );
        usort($fields, array( $this, "_sort" ) );
        array_walk( $fields, array( $this, "_undecorate" ) );
        
        return $fields;
    }
    
    /**
     * Sorting function
     * 
     * Sorts fields by 'order' field.
     * 
     * @param array $aData Field 'a'
     * @param array $bData Field 'b'
     * @since 0.3
     * @return int
     */
    protected function _sort( $aData, $bData ) {
        
        $a = $aData['order'];
        $b = $bData['order'];

        if ($a == $b) {
            return 0;
        }
        return ($a > $b) ? 1 : -1;
    }
    
    /**
     * Decorate Array.
     * 
     * This function is used to prepare array for Schwartzian Transformation
     * http://en.wikipedia.org/wiki/Schwartzian_transform
     * 
     * Basically this function converts element with key 'order' from int to array.
     * 
     * @param array $v Array element value
     * @param mixed $k Array element key 
     * @since 0.3
     * @return void 
     */
    protected function _decorate( &$v, $k ) {
        $v['order'] = array($v['order'], $k);
    }

    /**
     * Undecorate Array.
     * 
     * Reverses everything that self::_decorate() did.
     * 
     * @see self::_decorate()
     * 
     * @param array $v Array element value
     * @param mixed $k Array element key 
     * @since 0.3
     * @return void 
     */
    protected function _undecorate( &$v, $k ) {
        $v['order'] = $v['order'][0];
    }
    
    /**
     * Returns set form scheme or a part of it.
     * 
     * @param string $part
     * @return array
     * @throws Exception If $part param is invalid
     */
    public function get_scheme($part = null) {
        if( $part === null ) {
            return $this->_scheme;
        } elseif( !isset( $this->_scheme[$part] ) ) {
            throw new Exception("Form part [$part] does not exist.");
        } else {
            return $this->_scheme[$part];
        }
    }
    
    /**
     * Return value for selected field
     * 
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function get_value($field, $default = null) {
        if(isset($this->_form[$field]["value"])) {
            return $this->_form[$field]["value"];
        } else {
            return $default;
        }
    }
    
    /**
     * Set value for seected field
     * 
     * @param string $field
     * @param mixed $value
     */
    public function set_value($field, $value) {
        if(isset($this->_form[$field]) && is_array($this->_form[$field])) {
            $this->_form[$field]["value"] = $value;
        }
    }
    
    /**
     * Get values for all form fields
     * 
     * @return array
     */
    public function get_values() {
        $result = array();
        foreach($this->_form as $field => $data) {
            if(isset($data["value"]) && (!empty($data["value"]) || $data["value"]=="0")) {
                $result[$field] = $data["value"];
            }
        }
        return $result;
    }
}