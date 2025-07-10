# ReqRes Users Simple

A Drupal module that provides a block to display users from the reqres.in API.

## Features

- Configurable block to display users from the reqres.in API
- Pagination support
- Configurable number of items per page
- Configurable column labels
- Extension point to filter users

## Installation

1. Place this module in your Drupal installation under `web/modules/custom/`
2. Enable the module using Drush:
   ```
   ddev drush en reqres_users_simple -y
   ```

## Usage

1. Go to Block layout (`/admin/structure/block`)
2. Place the "Reqres Users Block" in your desired region
3. Configure the block:
   - Number of items per page
   - Email field label
   - Forename field label
   - Surname field label
4. Save the block

## Extension

The module provides an extension point to filter users. You can implement the event subscriber pattern to filter users before they are displayed.

Example implementation:

```php
namespace Drupal\my_module\EventSubscriber;

use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserFilterSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      UserFilterEvent::EVENT_NAME => 'filterUsers',
    ];
  }

  public function filterUsers(UserFilterEvent $event) {
    $users = $event->getUsers();
    $filtered_users = array_filter($users, function ($user) {
      // Your filtering logic here
      return true;
    });
    
    $event->setUsers(array_values($filtered_users));
  }
}
```

Then register your event subscriber in your module's services.yml file:

```yaml
services:
  my_module.user_filter_subscriber:
    class: Drupal\my_module\EventSubscriber\UserFilterSubscriber
    tags:
      - { name: event_subscriber }
```

## Testing

Run the unit tests with PHPUnit:

```
ddev exec ../vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/custom/reqres_users_simple
```
