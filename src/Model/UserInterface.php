<?php

namespace Drupal\reqres_users_simple\Model;

/**
 * Interface for user data model.
 */
interface UserInterface {

  /**
   * Gets the user ID.
   *
   * @return int
   *   The user ID.
   */
  public function getId(): int;

  /**
   * Gets the user email.
   *
   * @return string
   *   The user email.
   */
  public function getEmail(): string;

  /**
   * Gets the user first name.
   *
   * @return string
   *   The user first name.
   */
  public function getFirstName(): string;

  /**
   * Gets the user last name.
   *
   * @return string
   *   The user last name.
   */
  public function getLastName(): string;

  /**
   * Gets the user avatar URL.
   *
   * @return string
   *   The user avatar URL.
   */
  public function getAvatarUrl(): string;

  /**
   * Gets all user data as an array.
   *
   * @return array
   *   The user data.
   */
  public function toArray(): array;
  
  /**
   * Sets metadata for the user.
   *
   * @param array $metadata
   *   The metadata to set.
   *
   * @return $this
   */
  public function setMetadata(array $metadata): self;
  
  /**
   * Gets metadata for the user.
   *
   * @return array
   *   The metadata.
   */
  public function getMetadata(): array;

}
