<?php declare(strict_types=1);
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @private */
global $_shm_event_listeners;
$_shm_event_listeners = [];

function _load_event_listeners(): void
{
    global $_shm_event_listeners;

    $cache_path = data_path("cache/shm_event_listeners.php");
    if (SPEED_HAX && file_exists($cache_path)) {
        require_once($cache_path);
    } else {
        _set_event_listeners();

        if (SPEED_HAX) {
            _dump_event_listeners($_shm_event_listeners, $cache_path);
        }
    }
}

function _clear_cached_event_listeners(): void
{
    if (file_exists(data_path("cache/shm_event_listeners.php"))) {
        unlink(data_path("cache/shm_event_listeners.php"));
    }
}

function _set_event_listeners(): void
{
    global $_shm_event_listeners;
    $_shm_event_listeners = [];

    foreach (getSubclassesOf("Extension") as $class) {
        /** @var Extension $extension */
        $extension = new $class();

        // skip extensions which don't support our current database
        if (!$extension->info->is_supported()) {
            continue;
        }

        foreach (get_class_methods($extension) as $method) {
            if (substr($method, 0, 2) == "on") {
                $event = substr($method, 2) . "Event";
                $pos = $extension->get_priority() * 100;
                while (isset($_shm_event_listeners[$event][$pos])) {
                    $pos += 1;
                }
                $_shm_event_listeners[$event][$pos] = $extension;
            }
        }
    }
}

function _dump_event_listeners(array $event_listeners, string $path): void
{
    $p = "<"."?php\n";

    foreach (getSubclassesOf("Extension") as $class) {
        $p .= "\$$class = new $class(); ";
    }

    $p .= "\$_shm_event_listeners = array(\n";
    foreach ($event_listeners as $event => $listeners) {
        $p .= "\t'$event' => array(\n";
        foreach ($listeners as $id => $listener) {
            $p .= "\t\t$id => \$".get_class($listener).",\n";
        }
        $p .= "\t),\n";
    }
    $p .= ");\n";

    file_put_contents($path, $p);
}


/** @private */
global $_shm_event_count;
$_shm_event_count = 0;

/**
 * Send an event to all registered Extensions.
 */
function send_event(Event $event): Event
{
    global $tracer_enabled;

    global $_shm_event_listeners, $_shm_event_count, $_tracer;
    if (!isset($_shm_event_listeners[get_class($event)])) {
        return $event;
    }
    $method_name = "on".str_replace("Event", "", get_class($event));

    // send_event() is performance sensitive, and with the number
    // of times tracer gets called the time starts to add up
    if ($tracer_enabled) {
        $_tracer->begin(get_class($event));
    }
    // SHIT: http://bugs.php.net/bug.php?id=35106
    $my_event_listeners = $_shm_event_listeners[get_class($event)];
    ksort($my_event_listeners);

    foreach ($my_event_listeners as $listener) {
        if ($tracer_enabled) {
            $_tracer->begin(get_class($listener));
        }
        if (method_exists($listener, $method_name)) {
            $listener->$method_name($event);
        }
        if ($tracer_enabled) {
            $_tracer->end();
        }
        if ($event->stop_processing===true) {
            break;
        }
    }
    $_shm_event_count++;
    if ($tracer_enabled) {
        $_tracer->end();
    }

    return $event;
}
