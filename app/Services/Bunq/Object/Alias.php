<?php
/**
 * Alias.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

/**
 * Class Alias
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class Alias extends BunqObject
{
    /** @var string */
    private $name = '';
    /** @var string */
    private $type = '';
    /** @var string */
    private $value = '';

    /**
     * Alias constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->type  = $data['type'];
        $this->name  = $data['name'];
        $this->value = $data['value'];

        return;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }



}