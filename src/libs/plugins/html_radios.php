<?php
/**************
 * {html_radios name='id' values=$cust_ids output=$cust_names selected=$customer_id checkBoxClasses="form-control"  labelClasses="tplixCheckboxes"  separator='<br />'}
 * {html_radios name='id' options=$cust_checkboxes selected=$customer_id  labels=true labelClasses="" checkBoxClasses="" separator='<br />'}
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileHtml_radios
{
    function call(array $params = []): string
    {
        $idName = isset($params["name"]) ? $params["name"] : null;
        $selected = isset($params["selected"]) ? $params["selected"] : null;
        $separator = isset($params["separator"]) ? $params["separator"] : null;
        $assign = isset($params["assign"]) ? $params["assign"] : null;
        $radioButtonClasses = isset($params["radioButtonClasses"]) ? $params["radioButtonClasses"] : null;
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
        foreach($options as $k=>$v)
        {
            $isSelected = ($selected == $k) ? 'checked':'';
            if($labels)
            {
                if($label_ids){
                    $output .= '<label id="radio-box-label-'.$k.'" class="'.$labelClasses.'">';
                }else{
                    $output .= '<label class="'.$labelClasses.'">';
                }
            }
            if($label_ids){
                $output .= '<input type="radio" id="check-box-'.$k.'" name="'.$idName.'"  class="'.$radioButtonClasses.'" value="'.$k.'" '.$isSelected.' />';
            }else{
                $output .= '<input type="radio" name="'.$idName.'"  class="'.$radioButtonClasses.'" value="'.$k.'" '.$isSelected.' />';
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
