<?php

namespace FireflyIII\Database\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ScopeInterface;
use Illuminate\Database\Query\JoinClause;

/**
 * Class AccountScope
 *
 * @package FireflyIII\Database\Scope
 */
class AccountScope implements ScopeInterface
{
    static public $fields = ['accountRole' => 'account_role'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return void
     */
    public function apply(Builder $builder)
    {
        foreach (self::$fields as $name => $field) {
            $builder->leftJoin(
                'account_meta AS ' . $field, function (JoinClause $join) use ($field, $name) {
                $join->on($field . '.account_id', '=', 'accounts.id')->where($field . '.name', '=', $name);
            }
            );
        }

        //$builder->whereNull($model->getQualifiedDeletedAtColumn());
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return void
     */
    public function remove(Builder $builder)
    {
        foreach ($builder->joins as $join) {
            var_dump($join);
            exit;
        }
    }
}