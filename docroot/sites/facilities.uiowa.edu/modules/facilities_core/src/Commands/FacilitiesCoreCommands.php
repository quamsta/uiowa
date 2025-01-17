<?php

namespace Drupal\facilities_core\Commands;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\facilities_core\BuildingsProcessor;
use Drupal\facilities_core\ProjectsProcessor;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class FacilitiesCoreCommands extends DrushCommands {
  use LoggerChannelTrait;

  /**
   * The account_switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * A nullable array of data returned by the API.
   *
   * @var array|null
   */
  protected ?array $data;

  /**
   * Drush command constructor.
   *
   * @param \Drupal\Core\Session\AccountSwitcherInterface $accountSwitcher
   *   The account_switcher service.
   */
  public function __construct(AccountSwitcherInterface $accountSwitcher) {
    $this->accountSwitcher = $accountSwitcher;
  }

  /**
   * Triggers the building import.
   *
   * @command facilities_core:buildings_import
   * @aliases fm-buildings
   * @usage facilities_core:buildings_import
   *  Ideally this is done as a crontab that is only run once a day.
   */
  public function importBuildings() {
    // Switch to the admin user to pass access check.
    $this->accountSwitcher->switchTo(new UserSession(['uid' => 1]));

    $this->logger()->notice('Starting the facilities building content sync. This may take a little time if the information isn\'t cached.');
    $sync_service = new BuildingsProcessor();
    $sync_service->init();
    $sync_service->process();

    $arguments = [
      '@created' => $sync_service->getCreated(),
      '@updated' => $sync_service->getUpdated(),
      '@deleted' => $sync_service->getDeleted(),
      '@skipped' => $sync_service->getSkipped(),
    ];
    $this->logger()->notice(t('Facilities building content sync completed. @created buildings were created, @updated updated, @deleted deleted, @skipped skipped. That is neat.', $arguments));

    // Switch user back.
    $this->accountSwitcher->switchBack();
  }

  /**
   * Triggers the projects import.
   *
   * @command facilities_core:projects_import
   * @aliases fm-projects
   * @usage facilities_core:projects_import
   *  Ideally this is done as a crontab that is only run once a day.
   */
  public function importProjects() {
    // Switch to the admin user to pass access check.
    $this->accountSwitcher->switchTo(new UserSession(['uid' => 1]));

    $this->logger()->notice('Starting the facilities projects sync. This may take a little time if the information isn\'t cached.');
    $sync_service = new ProjectsProcessor();
    $sync_service->init();
    $sync_service->process();

    $arguments = [
      '@created' => $sync_service->getCreated(),
      '@updated' => $sync_service->getUpdated(),
      '@deleted' => $sync_service->getDeleted(),
      '@skipped' => $sync_service->getSkipped(),
    ];
    $this->logger()->notice('Facilities projects content sync completed. @created projects were created, @updated updated, @deleted deleted, @skipped skipped. That is neat.', $arguments);

    // Switch user back.
    $this->accountSwitcher->switchBack();
  }

}
