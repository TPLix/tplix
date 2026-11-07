<?php
use Tplix\Tplix;

class TplixCompileDump
{
    protected $outputObjects;
    protected $outputMethods;
    public function call(array $args = []): string
    {
        $var = isset($args["params"][0]) ? $args["params"][0]:$args[0];
        $show_methods = true;
        $this->outputMethods = $show_methods;
        if ($var === '$')
        {
            $out = '<div style="background:#aaa; padding:5px; margin:5px; color:#000;">data';
        } else {
            $out = '<div style="background:#aaa; padding:5px; margin:5px; color:#000;">dump';
        }
        $this->outputObjects = array();
        if (!is_array($var))
        {
            if (is_object($var))
            {
                return $this->exportObj('', $var);
            } else {
                return $this->exportVar('', $var);
            }
        }
        //$scope = $this->core->getScope();
        $scope = null;
        if ($var === $scope) {
            $out .= ' (current scope): <div style="background:#ccc;">';
        } else {
            $out .= ':<div style="padding-left:20px;">';
        }
        $out .= $this->export($var, $scope);
        return $out . '</div></div>';        
    }
    /**
     * @param $var
     * @param $scope
     *
     * @return string
     */
    protected function export($var, $scope)
    {
        $out = '';
        foreach ($var as $i => $v) {
            if (is_array($v) || (is_object($v) && $v instanceof Iterator)) {
                $out .= $i . ' (' . (is_array($v) ? 'array' : 'object: ' . get_class($v)) . ')';
                if ($v === $scope) {
                    $out .= ' (current scope):<div style="background:#ccc;padding-left:20px;">' . $this->export($v, $scope) . '</div>';
                } else {
                    $out .= ':<div style="padding-left:20px;">' . $this->export($v, $scope) . '</div>';
                }
            } elseif (is_object($v)) {
                $out .= $this->exportObj($i . ' (object: ' . get_class($v) . '):', $v);
            } else {
                $out .= $this->exportVar($i . ' = ', $v);
            }
        }
        return $out;
    }
    /**
     * @param $i
     * @param $v
     *
     * @return string
     */
    protected function exportVar($i, $v)
    {
        if (is_string($v) || is_bool($v) || is_numeric($v)) {
            return $i . htmlentities(var_export($v, true)) . '<br />';
        } elseif (is_null($v)) {
            return $i . 'null<br />';
        } elseif (is_resource($v)) {
            return $i . 'resource(' . get_resource_type($v) . ')<br />';
        } else {
            return $i . htmlentities(var_export($v, true)) . '<br />';
        }
    }

    /**
     * @param $i
     * @param $obj
     *
     * @return string
     */
    protected function exportObj($i, $obj)
    {
        if (array_search($obj, $this->outputObjects, true) !== false) {
            return $i . ' [recursion, skipped]<br />';
        }

        $this->outputObjects[] = $obj;

        $list = (array)$obj;

        $protectedLength = strlen(get_class($obj)) + 2;

        $out = array();

        if ($this->outputMethods) {
            $ref = new ReflectionObject($obj);

            foreach ($ref->getMethods() as $method) {
                if (!$method->isPublic()) {
                    continue;
                }

                if (empty($out['method'])) {
                    $out['method'] = '';
                }

                $params = array();
                foreach ($method->getParameters() as $param) {
                    $params[] = ($param->isPassedByReference() ? '&' : '') . '$' . $param->getName() . ($param->isOptional() ? ' = ' . var_export($param->getDefaultValue(), true) : '');
                }

                $out['method'] .= '(method) ' . $method->getName() . '(' . implode(', ', $params) . ')<br />';
            }
        }

        foreach ($list as $attributeName => $attributeValue) {
            if (property_exists($obj, $attributeName)) {
                $key = 'public';
            } elseif (substr($attributeName, 0, 3) === "\0*\0") {
                $key           = 'protected';
                $attributeName = substr($attributeName, 3);
            } else {
                $key           = 'private';
                $attributeName = substr($attributeName, $protectedLength);
            }

            if (empty($out[$key])) {
                $out[$key] = '';
            }

            $out[$key] .= '(' . $key . ') ';

            if (is_array($attributeValue)) {
                $out[$key] .= $attributeName . ' (array):<br />
							<div style="padding-left:20px;">' . $this->export($attributeValue, false) . '</div>';
            } elseif (is_object($attributeValue)) {
                $out[$key] .= $this->exportObj($attributeName . ' (object: ' . get_class($attributeValue) . '):', $attributeValue);
            } else {
                $out[$key] .= $this->exportVar($attributeName . ' = ', $attributeValue);
            }
        }

        $return = $i . '<br /><div style="padding-left:20px;">';

        if (!empty($out['method'])) {
            $return .= $out['method'];
        }

        if (!empty($out['public'])) {
            $return .= $out['public'];
        }

        if (!empty($out['protected'])) {
            $return .= $out['protected'];
        }

        if (!empty($out['private'])) {
            $return .= $out['private'];
        }

        return $return . '</div>';
    }    
}