# Loopee ExpressionEngine Plugin


## Foreach loop

The Loopee ExpressionEngine Plugin allows you to loop over custom parameters using tag pairs. It is useful in scenarios where you want to loop over values that aren't in channels.

    {exp:loopee foreach="red|green|blue"}
      My Color is {loopee_value}
    {/exp:loopee}

This would produce:

    My Color is red
    My Color is green
    My Color is blue

The <code>foreach</code> parameter lets you specify your values, separated by the <code>|</code> (pipe) character. If you need a pipe character in your value, you can escape it with <code>\|</code> (backslash)(pipe).

<code>{loopee_key}</code> can be used to get the 0-offset index of your parameter. (Like in a PHP array.)
<code>{loopee_value}</code> is the value.

You can also pass values as colon-separated key:value pairs.

    {exp:loopee foreach="red:ff0000|green:00ff00|blue:0000ff"}
      Color: {loopee_key}, RGB: {loopee_value}
    {/exp:loopee}

This would produce the following.

    Color: red, RGB: ff0000
    Color: green, RGB: 00ff00
    Color: blue, RGB: 0000ff

<code>{loopee_key}</code> is the pre-colon key.
<code>{loopee_value}</code> is post-colon value.

## Forint Loop

Loopee also provides functionality for looping through integer values.

    {exp:loopee forint="5" to="25" by="5"}{loopee_value},{/exp:loopee}

This would produce the following.

    5,10,15,20,25,

That trailing comma is annoying. Like many built-in ExpressionEngine tags, the <code>backspace</code> parameter is available.

    {exp:loopee forint="5" to="25" by="5" backspace="1"}{loopee_value},{/exp:loopee}

This would produce the more appealing

    5,10,15,20,25

## Custom Tags

You can use custom tags with both a forint loop or a foreach loop using the <code>as</code> parameter. Modifying the loops from previous examples.

    {exp:loopee foreach="red|green|blue" as="color"}
      My Color is {color}
    {/exp:loopee}

And similarly, with key:value pairs.

    {exp:loopee foreach="red:ff0000|green:00ff00|blue:0000ff" as="color:rgb"}
      Color: {color}, RGB: {rgb}
    {/exp:loopee}

Or with a forint loop.

    {exp:loopee forint="5" to="25" by="5" as="integer" backspace="1"}{integer},{/exp:loopee}

And that is the Loopee plugin.

## Inward Parsing

You can also put standard EE Module tags within the loop using the <code>parse="inward"</code> parameter.

So, for example, you could list all your channels.

    {exp:loopee parse="inward" foreach="blog|podcast|link-list" as="channel_id"}
    {exp:channel:info channel="{channel_id}"}
      <h2><a href="{channel_url}">{channel_title}</a></h2>
      <p>{channel_description}</p>
    {/exp:channel:info}
    {/exp:loopee}

## Compatibility

Loopee is built to be compatible with both EE 1 and EE 2, but so far has only been tested in the wild with EE 1. (Testers welcome.)

## Installation

**For EE 1.x**

Copy the <code>loopee/pi.loopee.php</code> file to your <code>system/plugins</code> directory.

**For EE 2.x**

Copy the <code>loopee</code> directory to your <code>system/expressionengine/third_party</code> directory.

## Legal Jargon

You're downloading software developed by an individual that is freely available on GitHub. You assume all responsibility for how you use it, and your mileage during use. (Developers love car analogies, right?)

I tried to code defensively and test for user input errors, but I am not responsible if you cause an infinite loop that crashes your server. Feel free to fork this and improve it.

Loopee is Dual licensed under the MIT or GPL Version 2 licenses, because that's what jQuery does and it's too late on a Monday night to look into what licensing I want to do for a freely distributed EE plugin. So that'll do for now.