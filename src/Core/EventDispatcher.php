<?php
namespace App\Core;

class EventDispatcher {
    private array $listeners = [];

    public function listen(string $event, callable $handler): void {
        $this->listeners[$event][] = $handler;
    }

    public function dispatch(string $event, array $payload = []): void {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $handler) {
                call_user_func($handler, $payload);
            }
        }
    }
}