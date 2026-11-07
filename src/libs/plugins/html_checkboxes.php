<?php
/**************
 * {html_checkboxes name='id' values=$cust_ids output=$cust_names selected=$customer_id checkBoxClasses="form-control"  labelClasses="tplixCheckboxes"  separator='<br />'}
 * {html_checkboxes name='id' options=$cust_checkboxes selected=$customer_id  labels=true labelClasses="" checkBoxClasses="" separator='<br />'}
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileHtml_checkboxes
{
    function call(array $params = []): string
    {
        $idName = isset($params["name"]) ? $params["name"] : null;
        $selected = isset($params["selected"]) ? $params["selected"] : null;
        $separator = isset($params["separator"]) ? $params["separator"] : null;
        $assign = isset($params["assign"]) ? $params["assign"] : null;
        $checkBoxClasses = isset($params["checkBoxClasses"]) ? $params["checkBoxClasses"] : null;
        $labelClasses = isset($params["labelClasses"]) ? $params["labelClasses"] : null;
        $values = isset($params["values"]) ? $params["values"] : [];
        $output = isset($params["output"]) ? $params["output"] : [];
        $options = isset($params["options"]) ? $params["options"] : [];
        $labels = isset($params["labels"]) ? $params["labels"] : false;
        $label_ids = isset($params["label_ids"]) ? $params["label_ids"] : false;
        if(empty($options))
        {
            for($i=0;$i<count($values);$i++)
            {
                $options[$values[$i]] = $output[$i];        
            }
        }
        $output = '';
        if(!is_array($selected))
        {
            $selected = str_replace([" , ",", "," ,"],[",",",",","],$selected);
            $selected = explode(",",$selected);
        }        
        foreach($options as $k=>$v)
        {
            $isSelected = in_array($k,$selected) ? 'checked':'';
            if($labels)
            {
                if($label_ids){
                    $output .= '<label id="check-box-label-'.$k.'" class="'.$labelClasses.'">';
                }else{
                    $output .= '<label class="'.$labelClasses.'">';
                }
            }
            if($label_ids){
                $output .= '<input type="checkbox" id="check-box-'.$k.'" name="'.$idName.'[]"  class="'.$checkBoxClasses.'" value="'.$k.'" '.$isSelected.' />';
            }else{
                $output .= '<input type="checkbox" name="'.$idName.'[]"  class="'.$checkBoxClasses.'" value="'.$k.'" '.$isSelected.' />';
            }
            $output .=$v;
            if($labels){$output .= '</label>';}
            $output .= ''.$separator.'';
        }
        if ($assign === null) return $output;
        Tplix::getInstance()->assign($assign,$output);
        return '';
    }
}
