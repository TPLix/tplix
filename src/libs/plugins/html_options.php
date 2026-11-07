<?php
/**************
 * {html_radios name='id' values=$cust_ids output=$cust_names selected=$customer_id checkBoxClasses="form-control"  labelClasses="tplixCheckboxes"  separator='<br />'}
 * {html_radios name='id' options=$cust_checkboxes selected=$customer_id  labels=true labelClasses="" checkBoxClasses="" separator='<br />'}
 * ***********/
use Tplix\Tplix;
use Tplix\Plugins;
class TplixCompileHtml_options
{
    function call(array $params = []): string
    {
        $idName = isset($params["name"]) ? $params["name"] : null;
        $selectID = isset($params["selectID"]) ? $params["selectID"] : null;
        $multiSelection = isset($params["multiSelection"]) ? $params["multiSelection"] : false;
        $selectClasses = isset($params["selectClasses"]) ? $params["selectClasses"] : null;
        $optGroupClasses = isset($params["optGroupClasses"]) ? $params["optGroupClasses"] : null;
        $optionClasses = isset($params["optionClasses"]) ? $params["optionClasses"] : null;
        $selected = isset($params["selected"]) ? $params["selected"] : null;
        $values = isset($params["values"]) ? $params["values"] : [];
        $output = isset($params["output"]) ? $params["output"] : [];
        $options = isset($params["options"]) ? $params["options"] : [];
        if(empty($options))
        {
            if(is_array($values)){
                for($i=0;$i<count($values);$i++)
                {
                    $options[$values[$i]] = $output[$i];        
                }                
            }else{
            $options = [$values=>$output];
            }
        }
        $isMultiDepth =  $this->arrayDepth($options);
        $isSelectClasses = ($selectClasses !== null) ? ' class="'.$selectClasses.'"':'';
        $isOptGroupClasses = ($optGroupClasses !== null) ? ' class="'.$optGroupClasses.'"':'';
        $isOptionClasses = ($optionClasses !== null) ? ' class="'.$optionClasses.'"':'';
        $isSelectID = ($selectID !== null) ? ' id="'.$selectID.'"':'';
        $isMultiSelection = ($multiSelection) ? ' multiple':'';
        $isMultiSelectionName = ($multiSelection) ? '[]':'';
        
        $output = '';
        if($idName !== null) $output .= '<select name="'.$idName.''.$isMultiSelectionName.'"'.$isSelectClasses.''.$isSelectID.' '.$isMultiSelection.'>';
        if($multiSelection)
        {
            if(!is_array($selected))
            {
                $selected = str_replace([" , ",", "," ,"],[",",",",","],$selected);
                $selected = explode(",",$selected);
            }
        }
        
        if($isMultiDepth)
        {
            foreach($options as $keys=>$values)
            {
                $output .= '<optgroup label="'.$keys.'"'.$isOptGroupClasses.'>';
                foreach($values as $k=>$v)
                {
                    if($multiSelection)
                    {
                        $isSelected = in_array($k,$selected) ? 'selected':'';
                    }else{
                        $isSelected = ($selected == $k) ? 'selected':'';
                    }
                    $output .= '<option value="'.$k.'"'.$isOptionClasses.' '.$isSelected.'>'.$v.'</option>';
                }
                $output .= '</optgroup>';
            }
        }else{
            foreach($options as $k=>$v)
            {
                    if($multiSelection)
                    {
                        $isSelected = in_array($k,$selected) ? 'selected':'';
                    }else{
                        $isSelected = ($selected == $k) ? 'selected':'';
                    }
                $output .= '<option value="'.$k.'"'.$isOptionClasses.' '.$isSelected.'>'.$v.'</option>';
            }
        }
        if($idName !== null) $output .= '</select>';
        return $output;
    }
    function arrayDepth(array $array): bool {
        foreach ($array as $value) {
            if (is_array($value)) return true;
        }
        return false;
    }
}
