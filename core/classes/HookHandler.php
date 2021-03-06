<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  Hook handler class
 */

class HookHandler
{
    private static $_events = [];

    private static $_hooks = [];

    // Register an event name
    // Params:  $event - name of event to add
    //          $description - human readable description
    //          $params - array of available parameters and their descriptions
    public static function registerEvent($event, $description, $params = []) {
        if (! isset(self::$_events[$event])) {
            self::$_events[$event] = [];
        }

        self::$_events[$event]['description'] = $description;
        self::$_events[$event]['params'] = $params;

        return true;
    }

    public static function registerHooks($hooks) {
        self::$_hooks = $hooks;

        return true;
    }

    // Register an event hook
    // Params:  $event - event name to hook into
    //          $hook - function name to execute, eg Class::method
    public static function registerHook($event, $hook) {
        if (! isset(self::$_events[$event])) {
            self::$_events[$event] = [];
        }

        self::$_events[$event]['hooks'][] = $hook;

        return true;
    }

    // Execute an event
    // Params:  $event - event name to call
    public static function executeEvent($event, $params = null) {
        if (! isset(self::$_events[$event])) {
            return false;
        }

        if (! is_array($params)) {
            $params = [];
        }

        if (! isset($params['event']))
            $params['event'] = $event;

        // Execute system hooks
        if (isset(self::$_events[$event]['hooks'])) {
            foreach (self::$_events[$event]['hooks'] as $hook) {
                call_user_func($hook, $params);
            }
        }

        // Execute user made webhooks
        foreach (self::$_hooks as $hook) {
            if (in_array($event, $hook['events'])) {
                if (isset($params['available_hooks'])) {
                    if (in_array($hook['id'], $params['available_hooks'])) {
                        $params['webhook'] = $hook['url'];
                        call_user_func($hook['action'], $params);
                    }
                } else {
                    $params['webhook'] = $hook['url'];
                    call_user_func($hook['action'], $params);
                }
            }
        }

        return true;
    }

    // Get a list of hooks
    public static function getHooks() {
        $return = [];

        foreach (self::$_events as $key => $item)
            $return[$key] = $item['description'];

        return $return;
    }

    // Get a certain hook
    public static function getHook($hook) {
        if (isset(self::$_events[$hook]))
            return self::$_events[$hook];
        
            return null;
    }

    // Get parameters
    public static function getParameters($event) {
        if (isset(self::$_events[$event]['parameters'])) {
            return self::$_events[$event]['parameters'];
        }

            return null;
    }
}
