<?php

namespace Drupal\notification\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class DeleteNotificationCommand implements CommandInterface
{
    // Implements Drupal\Core\Ajax\CommandInterface:render().
    public function render()
    {
        return array(
            'command' => 'DeleteNotification',
            'selector' => $this->selector,
        );
    }
}
