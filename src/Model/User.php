<?php

namespace Drupal\reqres_users_simple\Model;

/**
 * Base user model implementation.
 */
class User implements UserInterface {

  /**
   * The user ID.
   *
   * @var int
   */
  protected $id;

  /**
   * The user email.
   *
   * @var string
   */
  protected $email;

  /**
   * The user first name.
   *
   * @var string
   */
  protected $firstName;

  /**
   * The user last name.
   *
   * @var string
   */
  protected $lastName;

  /**
   * The user avatar URL.
   *
   * @var string
   */
  protected $avatarUrl;

  /**
   * Metadata for pagination and other information.
   *
   * @var array
   */
  protected $metadata = [];

  /**
   * Constructs a new User object.
   *
   * @param int $id
   *   The user ID.
   * @param string $email
   *   The user email.
   * @param string $first_name
   *   The user first name.
   * @param string $last_name
   *   The user last name.
   * @param string $avatar_url
   *   The user avatar URL.
   */
  public function __construct(
    int $id,
    string $email,
    string $first_name,
    string $last_name,
    string $avatar_url
  ) {
    $this->id = $id;
    $this->email = $email;
    $this->firstName = $first_name;
    $this->lastName = $last_name;
    $this->avatarUrl = $avatar_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail(): string {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName(): string {
    return $this->firstName;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName(): string {
    return $this->lastName;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvatarUrl(): string {
    return $this->avatarUrl;
  }
  
  /**
   * Gets the user avatar URL (alias for getAvatarUrl).
   *
   * @return string
   *   The user avatar URL.
   */
  public function getAvatar(): string {
    return $this->avatarUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $result = [
      'id' => $this->getId(),
      'email' => $this->getEmail(),
      'first_name' => $this->getFirstName(),
      'last_name' => $this->getLastName(),
      'avatar' => $this->getAvatarUrl(),
    ];
    
    // Add metadata if available
    if (!empty($this->metadata)) {
      $result['metadata'] = $this->metadata;
    }
    
    return $result;
  }
  
  /**
   * Sets metadata for the user.
   *
   * @param array $metadata
   *   The metadata to set.
   *
   * @return $this
   */
  public function setMetadata(array $metadata): self {
    $this->metadata = $metadata;
    return $this;
  }
  
  /**
   * Gets metadata for the user.
   *
   * @return array
   *   The metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

}
