<?php

namespace Firefly\Storage\Category;


class EloquentCategoryRepository implements CategoryRepositoryInterface {
    public function get() {
        return \Auth::user()->categories()->get();
    }

} 