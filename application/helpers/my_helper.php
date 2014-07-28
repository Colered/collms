<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('xml_viewpage'))
{
    function xml_viewpage($response)
    {
        $CI =& get_instance();
        $CI->load->helper('form');


        $attributes = array('id' => 'xmlviewform');
        echo form_open('xmlview',$attributes);
        echo form_hidden('responseData', $response);
        echo form_close();

        echo '<script type="text/javascript">document.getElementById("xmlviewform").submit();</script>';

    }
}