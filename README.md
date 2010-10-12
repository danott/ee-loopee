This Loopee ExpressionEngine Plugin allows you to loop over custom parameters using tag pairs. It is useful in scenarios where you want to loop over values that aren't in channels.

    {exp:loopee foreach="red|green|blue"}
      My Color is {loopee_value}
    {/exp:loopee}

This would produce:

    My Color is red
    My Color is green
    My Color is blue

The <code>foreach</code> parameter lets you specify your values, separated by the | (pipe) character. If you need a pipe character in your value, you can escape it with \| (backslash)(pipe).

<code>{loopee_key}</code> can be used to get the 0-offset index of your parameter. (Like in a PHP array.)
<code>{loopee_value}</code> is the value.

You can also pass values as colon-separated key:value pairs.

    {exp:loopee vars="red:ff0000|green:00ff00|blue:0000ff"}
      Color: {loopee_key}, RGB: {loopee_value}
    {/exp:loopee}

This would produce the following.

    Color: red, RGB: ff0000
    Color: green, RGB: 00ff00
    Color: blue, RGB: 0000ff

<code>{loopee_key}</code> is the pre-colon key.
<code>{loopee_value}</code> is post-colon value.

Loopee also provides functionality for looping through integer values.

    {exp:loopee forint="5" to="25" by="5"}{loopee_value},{/exp:loopee}

This would produce the following.

    5,10,15,20,25,

That trailing comma is annoying. Like many built-in ExpressionEngine tags, the <code>backspace</code> parameter is available.

    {exp:loopee forint="5" to="25" by="5" backspace="1"}{loopee_value},{/exp:loopee}

This would produce the more appealing

    5,10,15,20,25

BONUS: You can use custom tags with both a forint loop or a foreach loop using the <code>as</code> parameter. Modifying the loops from previous examples.

    {exp:loopee foreach="red|green|blue" as="color"}
      My Color is {color}
    {/exp:loopee}

And similarly, with key:value pairs.

    {exp:loopee vars="red:ff0000|green:00ff00|blue:0000ff" as="color:rgb"}
      Color: {color}, RGB: {rgb}
    {/exp:loopee}

Or with a forint loop.

    {exp:loopee forint="5" to="25" by="5" as="integer" backspace="1"}{integer},{/exp:loopee}

And that is the Loopee plugin.