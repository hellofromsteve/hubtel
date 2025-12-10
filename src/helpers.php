<?php

use HelloFromSteve\Hubtel\HubtelService; 

if (!function_exists('hubtel')) {
    /**
     * Get the Hubtel service instance or call a method directly.
     *
     * @param string|null $method
     * @param mixed ...$args
     * @return \HelloFromSteve\Hubtel\HubtelService|mixed
     */
    function hubtel(?string $method = null, ...$args)
    {
        // 1. Resolve the HubtelService from the Laravel Container
        // (Make sure you changed PaystackService to HubtelService here)
        $service = app(HubtelService::class);
        
        // 2. If no arguments are passed, return the full service instance
        if (is_null($method)) {
            return $service;
        }

        // 3. If a method name is passed, call it dynamically with the arguments
        return $service->$method(...$args);
    }
}