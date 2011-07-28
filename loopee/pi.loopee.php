<?php

/*!
 * Loopee ExpressionEngine Plugin v1.0
 * http://github.com/danott/
 *
 * Provides a simple tag pair to ExpressionEngine templates for looping over
 * values that would not necessarily fall within a weblog/channel.
 *
 * Copyright 2010, Daniel Ott
 * Dual licensed under the MIT or GPL Version 2 licenses.
 */


$plugin_info = array(
  'pi_name'         =>  'Loopee',
  'pi_version'      =>  '1.0',
  'pi_author'       =>  'Daniel Ott',
  'pi_author_url'   =>  'http://danott.us/',
  'pi_description'  =>  'Loop over a set of values, key:value pairs or integers',
  'pi_usage'        =>  'http://github.com/danott/danott-ee-loopee'
);


/**
 * Super Class
 *
 * @package    Loopee
 * @category  Plugin
 * @author    Daniel Ott
 * @copyright Copyright (c) 2010 Daniel Ott
 * @link      http://danott.us
 */
class Loopee {

  /* 
   * Initialize variables for EE1 and EE2 use in a single plugin.
   * They're later referenced in the constructor based on app version number
   */
  var $TMPL;
  var $return_data;


  /* Parameters
   * An array of 'key' => 'value' pairs that can be set for this plugin.
   * Utilized by @danott EE-Params
   *
   * foreach = the values to loop over. expected format is "ff0000|00ff00|0000ff" or "red:ff0000|green:00ff00|blue:0000ff"
   * as = the {tags} used within the loopee loop. can be a value, or a key/value pair. To correspond with above example: "rgb" or "color:rgb"
   */
  var $params = array(
    'foreach'   => NULL,
    'forint'    => 0,
    'to'        => 0,
    'by'        => 1,
    'as'        => array('loopee_key' => 'loopee_value'),
    'count'     => 'loopee_count',
    'backspace' => 0
  );
  
  
  /**
   * Loopee() Constructor
   *
   * Execute the function of this plugin, which is iterating the foreach or forint parameters.
   */
  function Loopee()
  {
    
    /* EE1 & EE2 Compatability! Booyah! */
    if (version_compare(APP_VER, '2', '<'))
    {
      // EE 1.x is in play
      global $TMPL;
      $this->TMPL =& $TMPL;
    } else {
      // EE 2.x is in play
      $this->EE  =& get_instance();
      $this->TMPL =& $this->EE->TMPL;
    }

    
    /* @danott ee-params
     * In pursuit of a one-size-fits all approach for setting EE Plugin parameters */
    foreach ($this->params as $param_array_key => &$param_array_value)
    {
      
      /* Save the default value in a temporary variable.
       * We will revert to this value later if the parameter isn't set by other means. */
      $default = $param_array_value;
      $param_array_value = NULL;

      
      /* First precedence -
       * Parameter set using the plugin parameters. */
      if ($this->TMPL->fetch_param($param_array_key))
      {
        $param_array_value = $this->TMPL->fetch_param($param_array_key);
      }

      /* Second precedence -
       * Parameter set using embed parameters. 
       * To set using embed parameters, a key must be set to TRUE in $this->set_from_embed['var'] = TRUE;
       * Also, it must not have been set in the plugin parameters.
       * If this is true, and the embed parameter exists, we'll set it. */
      if (! isset($param_array_value) 
         && isset($this->set_from_embed)
         && array_key_exists($param_array_key, $this->set_from_embed)
         && $this->set_from_embed[$param_array_key] === TRUE
         && isset($TMPL->embed_vars["embed:" . $param_array_key]))
      {
        $param_array_value = $TMPL->embed_vars["embed:" . $param_array_key];
      }

    
      /* PLUMBING
       * Seperate parameter into an array using the pipe character. This way, you can pass
       * {exp:plugin vars="1|2|3|4"}
       *
       * This will create an array in the form of
       * array("1","2","3","4");
       */
      if (isset($param_array_value))
      {
        // This lookbehind regex allows for escaping the pipe character \| in passed values
        $parts = preg_split("/(?<!\\\)\|/", $param_array_value, false, PREG_SPLIT_NO_EMPTY);
        
        foreach ($parts as $parts_index => &$part )
        {
          // Unescape pipe characters in all the parts
          $part = str_replace('\\|', '|', $part);
          
          /* COLONOSCOPY
           * In the "plumbing" stage we already split the value into an array.
           * This logic allows passing parameters in the form of:
           * {exp:plugin vars="foo:bar|biz:baz"}
           * And parsing it into an array such that
           * $params['vars'] = array('foo' => "bar", 'biz' => "baz")
           *
           * Try to seperate using the colon. On the other side of the colon is excrement. Poop joke. */
          if (preg_match("/^([\w-]*):(.*)$/", $part, $excrement))
          {
            // Set the 'key' => 'value' pair. ($excrement[0] is the entire matched $part)
            $parts[$excrement[1]] = $excrement[2];
            
            // The standard numbered index would still exist. It's not needed anymore.
            unset($parts[$parts_index]);            
          }
        }
        
        // The parsed value is no good to us if we don't actually save it. Save it!
        $param_array_value = $parts;
          
      } // end if (isset($param_array_value))
      
      
      /* Let's consider the case where we used the tag {exp:plugin var="value"}
       * At this point in the process we would have: $params['var'] = array(0 => 'value');
       * There's a 99% chance that is not what we want. We'd rather have: $params['var'] = 'value'
       * This if-statement does exactly that. */
      if (count($param_array_value) == 1 && array_key_exists(0, $param_array_value))
      {
        if ($param_array_key != 'foreach') // The one paramter we would like to keep as-is
        {
          $param_array_value = $param_array_value[0];          
        }
      }
            
      /* If we get to this point, and nothing has been set,
       * we can revert to the default value */
      if (! isset($param_array_value))
      {
        $param_array_value = $default;
      }

    } // end foreach ($params as $param_array_key => &$attributes)
    // end @danott ee-params


    // The data between the {exp:loopee} and {/exp:loopee} tags is what we're repeating for each parameter.
    $tagdata = $this->TMPL->tagdata;



    /* TAG SETUP
     * Setup the custom defined "as" tags that are going to be replaced by either of the two loopee loops.
     */
    
    // Assume the defaults, even if they do get reset, it's nice to be careful.
    $key_regex = '/{loopee_key}/';
    $value_regex = '/{loopee_value}/';
    $count_regex = '/{loopee_count}/';
    
    /* If this is an array, the "as" parameter was a key:value pair.
     * Want to make sure we can replace both the key and the value appropriately */
    if (is_array($this->params['as']))
    {
      /* Because there's no better way to get an anonymous-to-us array key,
       * just do a foreach even though there's (most likely) only one element. */
      foreach ($this->params['as'] as $key => $value)
      {
        $key_regex = '/{('.$key.')}/';
        $value_regex = '/{('.$value.')}/';
      }
    }
    /* There is no key assocated, the user is only replacing with a single value. Simply use the value */
    else
    {
      $value_regex = '/{('.$this->params['as'].')}/';      
    }
    
    $count_regex = '/{('.$this->params['count'].')}/';
    

    /* FOREACH
     * Do the foreach bit for every single value that was piped.
     */
    $loopee_count = 1;
    foreach ($this->params['foreach'] as $key => $value)
    {
      // Replace all the {keys} and {values} in one fancy preg_replace.
      $this->return_data .= preg_replace(array($key_regex,$value_regex,$count_regex), array($key,$value,$loopee_count), $tagdata);
      $loopee_count++;
    }



    /* FORINT
     * Do the forint bit using forint, to, by, as
     */

    // Ensure we're working with integers
    foreach (array('forint','to','by','backspace') as $key)
    {
      $this->params[$key] = intval($this->params[$key]);
    }

    // Used in server pieces of logic below. Is this a positive iteration? (TRUE or FALSE)
    $positive_iteration = ($this->params['by'] > 0) ? TRUE : FALSE;

    /* Prevent infinite loops so plugin users don't accidentally kill their server.
     * We iterate if:
     * The starting int and the ending int are not the same value
     * AND The iterator is a non-zero value
     * AND the direction of the iterator is the same as the direction from the start value to the end value
     * ) */
    if (
      $this->params['forint'] !== $this->params['to']
      &&
      $this->params['by'] !== 0
      &&
      $positive_iteration === ($this->params['forint'] < $this->params['to'])
    )
    {
      /* Actually do the for loop from 'forint' to 'to'
       * The big piece of logic test that:
       * The iterator is <= the 'to' value when moving in the positive direction
       * OR
       * The iterator is >= the 'to' value when moving in the negative direction
       */
      for ($iterator = $this->params['forint'], $loopee_count = 1; ($iterator <= $this->params['to'] && $positive_iteration) || ($iterator >= $this->params['to'] && ! $positive_iteration); $iterator += $this->params['by'], $loopee_count++)
      {
        $this->return_data .= preg_replace(array($value_regex, $count_regex), array($iterator, $loopee_count), $tagdata);
      }
    }

    /* Provide the nice backspace parameter functionality of many looping built-in ee functions.
     * No matter which loop was in effect, though it will mostly only be used in forint */
    $this->return_data = substr($this->return_data, 0, strlen($this->return_data) - $this->params['backspace']);
    
  } // end of Constructor function Loopee();

} // end of Loopee class

/* end of file pi.loopee.php */