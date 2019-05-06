<?php

namespace Sitenow\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;

/**
 * Defines commands in the Sitenow namespace.
 */
class MultisiteCommands extends BltTasks {

  /**
   * Require the --site-uri option so it can be used in postMultisiteInit.
   *
   * @hook validate recipes:multisite:init
   */
  public function validateMultisiteInit(CommandData $commandData) {
    if ($uri = $commandData->input()->getOption('site-uri')) {
      $commandData->input()->setOption('site-dir', $uri);
    }
    else {
      return new CommandError('Sitenow: you must supply the site URI via the --site-uri option.');
    }
  }

  /**
   * This will be called after the `recipes:multisite:init` command is executed.
   *
   * @hook post-command recipes:multisite:init
   */
  public function postMultisiteInit($result, CommandData $commandData) {
    $uri = $commandData->input()->getOption('site-uri');
    $machineName = $this->generateMachineName($uri);
    $root = $this->getConfigValue('repo.root');

    $data = <<<EOD

// Directory aliases for {$uri}.
\$sites['{$machineName}.uiowa.lndo.site'] = '{$uri}';
\$sites['{$machineName}.dev.drupal.uiowa.edu'] = '{$uri}';
\$sites['{$machineName}.test.drupal.uiowa.edu'] = '{$uri}';
\$sites['{$machineName}.prod.drupal.uiowa.edu'] = '{$uri}';

EOD;

    file_put_contents($root . '/docroot/sites/sites.php', $data, FILE_APPEND);

    $this->say('Added <comment>sites.php</comment> entries. Adjust as needed and commit.');
  }

  /**
   * Given a URI, create and return a unique ID.
   *
   * Used for internal subdomain and Drush alias group name, i.e. file name.
   *
   * @param string $uri
   *   The multisite URI.
   *
   * @return string
   *   The ID.
   */
  protected function generateMachineName($uri) {
    $parsed = parse_url("//{$uri}");

    if (substr($parsed['host'], -9) === 'uiowa.edu') {
      // Don't use the suffix if the host equals uiowa.edu.
      $machineName = substr($parsed['host'], 0, -10);

      // Reverse the subdomains.
      $parts = array_reverse(explode('.', $machineName));

      // Unset the www subdomain - considered the same site.
      $key = array_search('www', $parts);
      if ($key !== FALSE) {
        unset($parts[$key]);
      }
      $machineName = implode('', $parts);
    }
    else {
      // This site has a non-uiowa.edu TLD.
      $parts = explode('.', $parsed['host']);

      // Unset the www subdomain - considered the same site.
      $key = array_search('www', $parts);
      if ($key !== FALSE) {
        unset($parts[$key]);
      }

      // Pop off the suffix to be used later as a prefix.
      $extension = array_pop($parts);

      // Reverse the subdomains.
      $parts = array_reverse($parts);
      $machineName = $extension . '-' . implode('', $parts);
    }

    return $machineName;
  }

}
