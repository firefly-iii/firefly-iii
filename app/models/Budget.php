<?php

class Budget extends Component {
    protected $isSubclass = true;

    public static $factory = [
        'name' => 'string',
        'user_id' => 'factory|User',
        'class' => 'Budget'
    ];

} 