<?php
namespace Firefly\Helper\Preferences;
interface PreferencesHelperInterface
{

    public function set($name, $value);

    public function get($name, $default = null);

}