<?php

namespace Apigee\test;

use Symfony\Component\Yaml\Yaml;
use Apigee\Util\OrgConfig;
use Apigee\ManagementAPI\Organization;

abstract class AbstractAPITest extends \PHPUnit_Framework_TestCase {
  protected static $orgConfig;

  public function setUp() {
    if (!isset(self::$orgConfig)) {
      $config_file = __DIR__ . DIRECTORY_SEPARATOR . 'config.yml';
      if (!file_exists($config_file)) {
        throw new \Exception("Required config file $config_file does not exist.");
      }
      $config = Yaml::parse($config_file, TRUE);
      $missing_keys = array();
      $org = $endpoint = $user = $pass = '';
      foreach (array('org', 'endpoint', 'user', 'pass') as $key) {
        if (!array_key_exists($key, $config)) {
          $missing_keys[] = $key;
        }
        else {
          $$key = $config[$key];
        }
      }
      if (count($missing_keys) > 0) {
        throw new \Exception("Required key(s) missing from $config_file: " . join(', ', $missing_keys));
      }
      if (isset($config['options'])) {
        $options = $config['options'];
      }
      else {
        $options = array();
      }

      $oc = new OrgConfig($org, $endpoint, $user, $pass, $options);
      try {
        $organization = new Organization($oc);
        $organization->load($org);
      }
      catch (\Exception $e) {
        throw new \Exception("Unable to connect to $endpoint/o/$org as user $user. Are your credentials correct?");
      }

      self::$orgConfig = $oc;
    }
  }
}