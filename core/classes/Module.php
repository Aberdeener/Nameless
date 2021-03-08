<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Modules class
 */

abstract class Module
{
    private static $_modules = [];

    private $_name;
    private $_author;
    private $_version;
    private $_nameless_version;
    private $_load_before;
    private $_load_after;

    public function __construct($module, $name, $author, $version, $nameless_version, $load_before = [], $load_after = [])
    {
        self::$_modules[] = $module;
        $this->_name = $name;
        $this->_author = $author;
        $this->_version = $version;
        $this->_nameless_version = $nameless_version;

        // All modules should load after core
        if ($name != 'Core') {
            $load_after[] = 'Core';
        }

        $this->_load_before = $load_before;
        $this->_load_after = $load_after;
    }

    final protected function setName($name)
    {
        $this->_name = $name;
    }

    final protected function setVersion($version)
    {
        $this->_version = $version;
    }

    final protected function setNamelessVersion($nameless_version)
    {
        $this->_nameless_version = $nameless_version;
    }

    final protected function setAuthor($author)
    {
        $this->_author = $author;
    }

    abstract public function onInstall();

    abstract public function onUninstall();

    // TODO: Implement

    abstract public function onEnable();

    abstract public function onDisable();

    abstract public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template);

    public static function loadPage($user, $pages, $cache, $smarty, $navs, $widgets, $template = null)
    {
        foreach (self::$_modules as $module) {
            $module->onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template);
        }
    }

    public static function getModules()
    {
        return self::$_modules;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getAuthor()
    {
        return $this->_author;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getNamelessVersion()
    {
        return $this->_nameless_version;
    }

    public function getLoadBefore()
    {
        return $this->_load_before;
    }

    public function getLoadAfter()
    {
        return $this->_load_after;
    }

    private static function findBeforeAfter($modules, $current)
    {
        $before = [$current];
        $after = [];
        $found = false;

        foreach ($modules as $module) {
            if ($found) {
                $after[] = $module;
            } elseif ($module == $current) {
                $found = true;
            } else {
                $before[] = $module;
            }
        }

        return [$before, $after];
    }

    public static function determineModuleOrder()
    {
        $module_order = ['Core'];
        $failed = [];

        foreach (self::$_modules as $module) {
            if ($module->getName() == 'Core') {
                continue;
            }

            for ($n = 0; $n < count($module_order); $n++) {
                $before_after = self::findBeforeAfter($module_order, $module_order[$n]);

                if (! array_diff($module->getLoadAfter(), $before_after[0]) && ! array_diff($module->getLoadBefore(), $before_after[1])) {
                    array_splice($module_order, $n + 1, 0, $module->getName());
                    continue 2;
                }
            }

            $failed[] = $module->getName();
        }

        return ['modules' => $module_order, 'failed' => $failed];
    }
}
