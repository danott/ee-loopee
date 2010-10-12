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
  'pi_usage'        =>  Loopee::usage()
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
    'backspace' => 0
  );
  
  
  /**
   * Loopee() Constructor
   *
   * Execute the function of this plugin, which is iterating over the vars parameter.
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
        $param_array_value = $param_array_value[0];
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



    /*
     * Setup the custom defined "as" tags that are going to be replaced by either of the two loopee loops.
     */
    
    // Assume the defaults, even if they do get reset, it's nice to be careful.
    $key_regex = '/{loopee_key}/';
    $value_regex = '/{loopee_value}/';
    
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
      
        

    /*
     * Do the foreach bit for every single value that was piped.
     */
    foreach ($this->params['foreach'] as $key => $value)
    {
      // Replace all the {keys} and {values} in one fancy preg_replace.
      $this->return_data .= preg_replace(array($key_regex,$value_regex), array($key,$value), $tagdata);
    }

    /*
     * Do the forint bit using forint, to, by, as
     */

    // Ensure we're working with integers
    foreach (array('forint','to','by','backspace') as $key)
    {
      $this->params[$key] = intval($this->params[$key]);
    }

    // Used in many pieces of logic below. Is this a positive iteration? (TRUE or FALSE)
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
      for ($iterator = $this->params['forint']; ($iterator <= $this->params['to'] && $positive_iteration) || ($iterator >= $this->params['to'] && ! $positive_iteration); $iterator += $this->params['by'])
      {
        $this->return_data .= preg_replace($value_regex, $iterator, $tagdata);
      }
    }

    // Provide the nice functionality of many looping built-in ee functions. backspace="2"
    $this->return_data = substr($this->return_data, 0, strlen($this->return_data) - $this->params['backspace']);
    
  } // end of Constructor function Loopee();

  /**
   * usage()
   * Information for the plugin within the EE Control Panel
   * @TODO - Create this
   */
  function usage()
  {
    ob_start();
?>
<h1>Loopee ExpressionEngine Plugin</h1>

<h2>Foreach loop</h2>

<p>The Loopee ExpressionEngine Plugin allows you to loop over custom parameters using tag pairs. It is useful in scenarios where you want to loop over values that aren't in channels.</p>

<pre><code>{exp:loopee foreach="red|green|blue"}
  My Color is {loopee_value}
{/exp:loopee}
</code></pre>

<p>This would produce:</p>

<pre><code>My Color is red
My Color is green
My Color is blue
</code></pre>

<p>The <code>foreach</code> parameter lets you specify your values, separated by the <code>|</code> (pipe) character. If you need a pipe character in your value, you can escape it with <code>\|</code> (backslash)(pipe).</p>

<p><code>{loopee_key}</code> can be used to get the 0-offset index of your parameter. (Like in a PHP array.)
<code>{loopee_value}</code> is the value.</p>

<p>You can also pass values as colon-separated key:value pairs.</p>

<pre><code>{exp:loopee vars="red:ff0000|green:00ff00|blue:0000ff"}
  Color: {loopee_key}, RGB: {loopee_value}
{/exp:loopee}
</code></pre>

<p>This would produce the following.</p>

<pre><code>Color: red, RGB: ff0000
Color: green, RGB: 00ff00
Color: blue, RGB: 0000ff
</code></pre>

<p><code>{loopee_key}</code> is the pre-colon key.
<code>{loopee_value}</code> is post-colon value.</p>

<h2>Forint Loop</h2>

<p>Loopee also provides functionality for looping through integer values.</p>

<pre><code>{exp:loopee forint="5" to="25" by="5"}{loopee_value},{/exp:loopee}
</code></pre>

<p>This would produce the following.</p>

<pre><code>5,10,15,20,25,
</code></pre>

<p>That trailing comma is annoying. Like many built-in ExpressionEngine tags, the <code>backspace</code> parameter is available.</p>

<pre><code>{exp:loopee forint="5" to="25" by="5" backspace="1"}{loopee_value},{/exp:loopee}
</code></pre>

<p>This would produce the more appealing</p>

<pre><code>5,10,15,20,25
</code></pre>

<h2>Custom Tags</h2>

<p>You can use custom tags with both a forint loop or a foreach loop using the <code>as</code> parameter. Modifying the loops from previous examples.</p>

<pre><code>{exp:loopee foreach="red|green|blue" as="color"}
  My Color is {color}
{/exp:loopee}
</code></pre>

<p>And similarly, with key:value pairs.</p>

<pre><code>{exp:loopee vars="red:ff0000|green:00ff00|blue:0000ff" as="color:rgb"}
  Color: {color}, RGB: {rgb}
{/exp:loopee}
</code></pre>

<p>Or with a forint loop.</p>

<pre><code>{exp:loopee forint="5" to="25" by="5" as="integer" backspace="1"}{integer},{/exp:loopee}
</code></pre>

<p>And that is the Loopee plugin.</p>

<h2>Inward Parsing</h2>

<p>You can also put standard EE Module tags within the loop using the <code>parse="inward"</code> parameter.</p>

<p>So, for example, you could list all your channels.</p>

<pre><code>{exp:loopee parse="inward" foreach="blog|podcast|link-list" as="channel_id"}
{exp:channel:info channel="{channel_id}"}
  &lt;h2&gt;&lt;a href="{channel_url}"&gt;{channel_title}&lt;/a&gt;&lt;/h2&gt;
  &lt;p&gt;{channel_description}&lt;/p&gt;
{/exp:channel:info}
{/exp:loopee}
</code></pre>

<h2>Compatibility</h2>

<p>Loopee is built to be compatible with both EE 1 and EE 2, but so far has only been tested in the wild with EE 1. (Testers welcome.)</p>

<h2>Installation</h2>

<p><strong>For EE 1.x</strong></p>

<p>Copy the <code>loopee/pi.loopee.php</code> file to your <code>system/plugins</code> directory.</p>

<p><strong>For EE 2.x</strong></p>

<p>Copy the <code>loopee</code> directory to your <code>system/expressionengine/third_party</code> directory.</p>

<h2>Legal Jargon That My Lawyer Friend's Heart Would Melt Over</h2>

<p>You're downloading software developed by an individual that is freely available on GitHub. You assume all responsibility for how you use it, and your mileage during use. (Developers love car analogies, right?)</p>

<p>I tried to code defensively and test for user input errors, but I am not responsible if you cause an infinite loop that crashes your server. Feel free to fork this and improve it.</p>
<?php
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
  
  } // end of function usage()

} // end of Loopee class

/* end of file pi.loopee.php */