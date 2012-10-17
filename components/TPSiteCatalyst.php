<?php
/**
 * Adobe SiteCatalyst Component
 *
 * @author Philip Lawrence <philip@misterphilip.com>
 * @link http://misterphilip.com
 * @link http://tagpla.net
 * @link https://github.com/TagPlanet/yii-analytics-sc
 * @copyright Copyright &copy; 2012 Philip Lawrence
 * @license http://tagpla.net/licenses/MIT.txt
 * @version 1.0.1
 */
class TPSiteCatalyst extends CApplicationComponent
{
    protected $settings = array('namespace','createObject','s_account','s_codeLocation','autoRender','autoPageview','renderMoble','renderHead');
    
    protected $namespace = 's';
    protected $createObject = true;
    protected $s_account = array();
    protected $s_codeLocation = '';
    protected $autoRender = false;
    protected $autoPageview = true;
    protected $renderMobile = false;
    protected $renderHead = false;
    
            
    /**
     * Type of quotes to use for values
     */
    const Q = "'";

    /**
     * Method data to be pushed into the s object
     * @var array
     */
    private $_data = array();

    /**
     * init function - Yii automatically calls this
     */
    public function init()
    {
        // Nothing needs to be done initially, huzzah!
    }

    /**
     * Render and return the SiteCatalyst data
     * @return mixed
     */
    public function render()
    {
        // Get the render location
        $renderLocation = ($this->renderHead) ? CClientScript::POS_HEAD : CClientScript::POS_END;
        
        // Get the namespace
        $n = (($this->namespace != '' && ctype_alnum($this->namespace)) ? $this->namespace : 's');
        
        // Check for s_code rendering
        if($this->s_codeLocation != '')
            Yii::app()->clientScript->registerScriptFile($this->s_codeLocations, $renderLocation);
        
        // Start the rendering...
        $js = '';
        
        // Do we need to create the object?
        if($this->createObject)
            $js.= 'var ' . $n . ' = ' . $n . '_account(' . self::Q . $this->_formatVariable('s_account', $this->s_account) . self::Q . ');' . PHP_EOL;
        
        // Go through the data
        foreach($this->_data as $var => $value)
        {
            $js.= $n . '.' . $var . ' = ' . self::Q . preg_replace('~(?<!\\\)'. self::Q . '~', '\\'. self::Q, $this->_formatVariable($var, $value)) . self::Q . ';' . PHP_EOL;
        }
        
        // Should we add s.t()?
        if($this->autoPageview)
            $js.= $n . '.t();' . PHP_EOL;
        
        // TagPla.net copyright... please leave in here!
        $js.= '// Adobe SiteCatalyst Extension provided by TagPla.net' . PHP_EOL;
        $js.= '// https://github.com/TagPlanet/yii-analytics' . PHP_EOL;
        $js.= '// Copyright 2012, TagPla.net & Philip Lawrence' . PHP_EOL;
        
        
        // Should we auto add in the analytics tag?
        if($this->autoRender)
        {
            Yii::app()->clientScript
                    ->registerScript('TPSiteCatalyst', $js, CClientScript::POS_HEAD);
        }
        else
        {
            return $js;
        }
        
        return;
    }
    
    /**
     * Wrapper for getting / setting options
     *
     * @param string $name
     * @param mixed  $value
     * @return mixed (success if set / value if get)
     */
    public function setting($name, $value = null)
    {
        if(in_array($name, $this->settings))
        {
            // Get value
            if($value === null)
            {
                return $this->$name;
            }
            
            $this->$name = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Magic Method for setting settings
     * @param string $name
     * @param mixed $value
     * @param array  $arguments
     */
    public function __set($name, $value)
    {        
        if(in_array($name, $this->settings))
            return $this->setting($name, $value);
        
        if($this->_validVariableName($name))
        {
            // iz gud
            $this->_data[$name] = $value;        
        }
    }
    
    /**
     * Valid variable name
     * Verifies the variable name passed in is OK
     * 
     * @param string $name
     * @returns bool
     */
    protected function _validVariableName($name)
    {
        // @TODO: Update this list with more
        $named = array('pageName','channel','server','campaign','products','TnT','events','pageType','purchaseID', 'transactionID','state','zip','currencyCode','pageType');
        $count = array('hier', 'eVar', 'prop');
        
        // Check for named
        if(in_array($name, $named))
            return true;
        
        // Check against numbered vars
        foreach($count as $var)
            if(strpos($name, $var) === 0)
                return true;
        
        // No matches :(
        return false;
    }    
    
    /**
     * Format variable (non-mobile)
     * Formats the variable for output
     * 
     * @param string $name
     * @returns bool
     */
    protected function _formatVariable($variable, $data)
    {
        switch($variable)
        {
            // s.products variable is probably the trickiest
            case 'products':
                // Do we have a string already, or an array?
                if(is_array($data))
                {
                    $allowed = array('category', 'sku', 'quantity', 'price', 'events', 'evars');
                    $products = array();
                    
                    // Loop through the incoming data
                    foreach($data as $key => $product)
                    {
                        // Which data source to use?
                        $dn = 'product';
                        if(!is_array($data[$key]))
                            $dn = 'data';
                        
                        $d =& $$dn;
                        
                        // Build the string
                        $p = array();
                        foreach($allowed as $v)
                            $p[] = (isset($d[$v]) ? $d[$v] : '');
                        $products[] = implode(';', $p);
                    }
                    return implode(',', $products);
                }
                
                // A regular string was passed in
                return $data;
            break;
            
            // Events could be an array
            case 'events':
                if(is_array($data))
                    $data = implode(',', $data);
                return $data;
            break;
            
            // RSID list could be an array
            case 's_account':
                if(is_array($data))
                    $data = implode(',', $data);
                return $data;
            break;
            
            // No formatting required
            default:
                return $data;
            break;
        }
    }
}