<?php

class Html
{
    public static function Div($class, $contents)
    {
        return "<div class='$class'>$contents</div>";
    }

    public static function DivWithId($id, $class, $contents)
    {
        return "<div id='$id' class='$class'>$contents</div>";
    }

    public static function Span($class, $contents)
    {
        return "<span class='$class'>$contents</span>";
    }

    /**
     * @param $class string
     * @param $title string
     * @param $onclick string
     * @param $contents string
     * @return string
     */
    public static function A($class, $title, $onclick, $contents)
    {
        return "<a class='$class' title='$title' onclick='$onclick return false;'>$contents</a>";
    }

    /* @param $name string
     * @return string
     */
    public static function Icon($name)
    {
        return Html::Span("inline_icon img_$name", '');
    }
    public static function LargeIcon($name)
    {
        return Html::Span("inline_icon_large img_$name", '');
    }
    public static function Heading($contents)
    {
        return Html::Div('heading', "$contents");
    }

    public static function SubHeading($contents)
    {
        return Html::Div('sub_heading', "$contents");
    }

    public static function P($contents)
    {
        return "<p>$contents</p>";
    }

    public static function LinkButton($caption, $contentKey)
    {
        return Content::GetNavLink($caption, $contentKey, 'link_button');
    }
    public static function UL($contents)
    {
        return "<ul>$contents</ul>";
    }

    public static function LI($contents)
    {
        return "<li>$contents</li>";
    }

    public static function Tag($tag, $contents)
    {
        return "<$tag>$contents</$tag>";
    }

    /* @param $id string
     * @param $name string
     * @param $value string
     * @param $type string
     * @param $label string
     * @param $placeholder string
     * @param $required bool
     * @param $autofocus bool
     * @param $pattern string
     * @param $autocomplete string
     * @return string
     */
    public static function Input($id, $name, $value = '', $type = 'text', $label, $placeholder = '', $required = true, $autofocus = false, $pattern = '', $autocomplete = '') {
        $req = $required ? 'required' : '';
        $af = $autofocus ? 'autofocus' : '';
        $pat = strlen($pattern) > 0 ? "pattern=\"$pattern\"" : '';
        $ac = strlen($autocomplete) > 0 ? "autocomplete=\"$autocomplete\" x-autocompletetype=\"$autocomplete\"" : '';
        if (strlen($label))
            $lab = self::Label($label, $id);
        else
            $lab = '';
        return "$lab<input id=\"$id\" type=\"$type\" name=\"$name\" placeholder=\"$placeholder\" value=\"$value\" $pat $req $af $ac/>";
    }
    public static function Checkbox($id, $name, $checked, $label, $disabled = false, $onclick = '')
    {
        $chk = $checked ? 'checked' : '';
        $dis = $disabled ? 'disabled' : '';
        $oc = strlen($onclick) > 0 ? "onclick=\"$onclick\"" : '';
        $cb = "<input type='checkbox' id='$id' name='$name' $chk $dis $oc />";
        if (strlen($label))
            $cb .= self::Label($label, $id);
        return $cb;
    }
    public static function Label($contents, $for = '')
    {
        return strlen($for) ? "<label for=\"$for\">$contents</label>" : "<label>$contents</label>";
    }

    public static function Form($contents, $class = 'standard_form', $id = '')
    {
        return strlen($id) ?
            "<form id=\"$id\" class=\"$class\" method=\"post\" >$contents</form>" :
            "<form class=\"$class\" method=\"post\" >$contents</form>";
    }

    public static function Fieldset($contents)
    {
        return "<fieldset>$contents</fieldset>";
    }

    public static function SubmitButton($caption, $disabled = false)
    {
        $keySubmit = Key::KEY_SUBMIT_BUTTON;
        $disabledAttr = $disabled ? ' disabled' : '';
        return "<input id=\"$keySubmit\" type=\"submit\" class=\"standard_button{$disabledAttr}\" value=\"$caption\" $disabledAttr />";
    }
    public static function CancelButton($contentKey)
    {
        return Content::GetNavLink('Cancel', $contentKey, 'standard_button cancel');
    }
    public static function Img($src)
    {
        return "<img src=\"$src\" />";
    }
    public static function Br()
    {
        return '<br>';
    }

    public static function HR()
    {
        return '<hr>';
    }

    public static function HiddenInput($name, $value)
    {
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
    }

    public static function TagWithStyle($tag, $style, $contents)
    {
        return "<$tag style='$style'>$contents</$tag>";
    }

    /**
     * @param $tag string
     * @param $class string
     * @param $contents string
     * @return string
     */
    public static function TagWithClass($tag, $class, $contents)
    {
        return "<$tag class='$class'>$contents</$tag>";
    }
    public static function Script($script)
    {
        return "<script>$script</script>";
    }

    /* @param $id string
     * @param $name string
     * @param $value string
     * @param $min string
     * @param $max string
     * @return string
     */
    public static function DateInput($id, $name, $value, $min, $max)
    {
        return "<input type=\"date\" id=\"$id\" name=\"$name\" value=\"$value\" min=\"$min\" max=\"$max\" required />";
    }
}