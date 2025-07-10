# ReqRes Users Simple

A Drupal module that provides integration with multiple user API sources using SOLID principles.

## Features

- Integration with multiple user API sources
- Unified user data model across different APIs
- Adapter pattern for converting different API data formats
- Composite pattern for aggregating users from multiple sources
- Pagination support
- Extension point to filter users via events
- Configurable block to display users
- Demo controller to showcase multi-API integration

## Installation

1. Place this module in your Drupal installation under `web/modules/custom/`
2. Enable the module using Drush:
   ```
   ddev drush en reqres_users_simple -y
   ```

## Usage

### Block Display

1. Go to Block layout (`/admin/structure/block`)
2. Place the "Reqres Users Block" in your desired region
3. Configure the block:
   - Number of items per page
   - Email field label
   - First name field label
   - Last name field label
4. Save the block

### Multi-API Demo

Visit `/reqres-users-simple/multi-api-demo` to see users aggregated from all configured API sources with pagination.

### Programmatic Usage

```php
// Get the UserProviderManager service
$userProviderManager = \Drupal::service('reqres_users_simple.user_provider_manager');

// Get users from all providers with pagination
$users = $userProviderManager->getFilteredUsers($page, $per_page);

// Get total user count across all providers
$totalUsers = $userProviderManager->getTotalUsers();

// Get total pages across all providers
$totalPages = $userProviderManager->getTotalPages($per_page);
```

## Architecture

### Overview

The module is built using SOLID principles and design patterns to provide a flexible and extensible architecture for integrating with multiple user API sources.

### Components

#### Interfaces

- `UserApiClientInterface`: Interface for API clients that fetch user data
- `UserInterface`: Interface for the user data model
- `UserDataAdapterInterface`: Interface for adapters that convert API-specific data to user models
- `UserProviderInterface`: Interface for user providers that abstract different API sources

#### Models

- `User`: Base implementation of `UserInterface`

#### API Clients

- `ReqresApiClient`: Client for the Reqres API
- `JsonPlaceholderApiClient`: Client for the JSONPlaceholder API
- `RandomUserApiClient`: Client for the Random User API

#### Adapters

- `ReqresUserDataAdapter`: Converts Reqres API data to User models
- `JsonPlaceholderUserDataAdapter`: Converts JSONPlaceholder API data to User models
- `RandomUserDataAdapter`: Converts Random User API data to User models

#### Providers

- `ReqresUserProvider`: Provider for users from the Reqres API
- `JsonPlaceholderUserProvider`: Provider for users from the JSONPlaceholder API
- `RandomUserProvider`: Provider for users from the Random User API
- `CompositeUserProvider`: Aggregates users from multiple providers

#### Services

- `UserProviderManager`: Main service for fetching and filtering users from all providers

#### Events

- `UserFilterEvent`: Event for filtering users before they are returned

### Extension Points

#### Filtering Users

Implement an event subscriber to filter users:

```php
namespace Drupal\my_module\EventSubscriber;

use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\reqres_users_simple\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserFilterSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      UserFilterEvent::EVENT_NAME => 'filterUsers',
    ];
  }

  public function filterUsers(UserFilterEvent $event) {
    $users = $event->getUsers();
    $filtered_users = array_filter($users, function (UserInterface $user) {
      // Your filtering logic here
      return $user->getFirstName() !== 'John';
    });
    
    $event->setUsers(array_values($filtered_users));
  }
}
```

Register your event subscriber:

```yaml
services:
  my_module.user_filter_subscriber:
    class: Drupal\my_module\EventSubscriber\UserFilterSubscriber
    tags:
      - { name: event_subscriber }
```

#### Adding New API Sources

1. Create a new API client implementing `UserApiClientInterface`
2. Create a new adapter implementing `UserDataAdapterInterface`
3. Create a new provider implementing `UserProviderInterface`
4. Register the new services in your module's services.yml file
5. Add the provider to the composite provider

Example services.yml:

```yaml
services:
  my_module.new_api_client:
    class: Drupal\my_module\ApiClient\NewApiClient
    arguments: ['@http_client_factory', '@logger.factory', '@cache.default']
    
  my_module.new_api_adapter:
    class: Drupal\my_module\Adapter\NewApiAdapter
    
  my_module.new_api_provider:
    class: Drupal\my_module\Provider\NewApiProvider
    arguments: ['@my_module.new_api_client', '@my_module.new_api_adapter']
    tags:
      - { name: reqres_users_simple.user_provider }
```

## Testing

Run the unit tests with PHPUnit:

```
ddev exec ../vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/custom/reqres_users_simple
```

## Class Diagram

```
+---------------------+      +----------------+      +---------------------+
| UserApiClientInterface <---- ReqresApiClient |      | UserDataAdapterInterface |
+---------------------+      +----------------+      +---------------------+
         ^                                                  ^
         |                                                  |
+------------------------+                      +------------------------+
| JsonPlaceholderApiClient |                      | ReqresUserDataAdapter |
+------------------------+                      +------------------------+
         ^                                                  ^
         |                                                  |
+------------------------+                      +------------------------+
| RandomUserApiClient |                      | JsonPlaceholderUserDataAdapter |
+------------------------+                      +------------------------+
                                                          ^
                                                          |
                                               +------------------------+
                                               | RandomUserDataAdapter |
                                               +------------------------+

+---------------------+      +------------------+      +---------------------+
| UserProviderInterface <---- ReqresUserProvider |      | UserInterface |
+---------------------+      +------------------+      +---------------------+
         ^                                                  ^
         |                                                  |
+------------------------+                      +------------------------+
| JsonPlaceholderUserProvider |                      | User |
+------------------------+                      +------------------------+
         ^
         |
+------------------------+
| RandomUserProvider |
+------------------------+
         ^
         |
+------------------------+
| CompositeUserProvider |
+------------------------+
         ^
         |
+------------------------+
| UserProviderManager |
+------------------------+
```

## API Documentation

### UserApiClientInterface

```php
interface UserApiClientInterface {
  public function getUsers(int $page, int $per_page);
  public function getTotalPages(int $per_page);
  public function getTotalUsers();
}
```

### UserInterface

```php
interface UserInterface {
  public function getId();
  public function getFirstName();
  public function getLastName();
  public function getEmail();
  public function getAvatar();
}
```

### UserDataAdapterInterface

```php
interface UserDataAdapterInterface {
  public function convertToUser(array $data);
}
```

### UserProviderInterface

```php
interface UserProviderInterface {
  public function getUsers(int $page, int $per_page);
  public function getTotalPages(int $per_page);
  public function getTotalUsers();
  public function getProviderName();
}
```
