<?php

namespace Firefly\Helper\Form;

/**
 * Class FormHelper
 *
 * @package Firefly\Form
 */
class FormHelper
{
    /**
     * @param null $value
     *
     * @return string
     */
    public function budget($value = null)
    {

        $str = '<select name="budget_id" class="form-control">';

        $str .= '<option value="0" label="(no budget)"';
        if (is_null($value) || intval($value) == 0) {
            $str .= ' selected="selected"';
        }
        $str .= '</option>';

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgets */
        $budgets = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $list = $budgets->getAsSelectList();
        foreach ($list as $id => $name) {
            $str .= '<option value="' . e($id) . '" label="' . e($name) . '"';
            if ($id == intval($value)) {
                $str .= ' selected="selected"';
            }
            $str .= '>' . e($name) . '</option>';
        }


        $str .= '</select>';

        return $str;
    }


}