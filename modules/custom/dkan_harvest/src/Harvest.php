<?php

namespace Drupal\dkan_harvest;

use Drupal\dkan_harvest\Extract;
//use Drupal\dkan_harvest\Extract\DataJson;
use Drupal\dkan_harvest\DataJson;
use Drupal\dkan_harvest\Transform;
use Drupal\dkan_harvest\Filter;
use Drupal\dkan_harvest\Override;
use Drupal\dkan_harvest\Load;
use Drupal\dkan_harvest\Dkan8;
use Drupal\dkan_harvest\Log;
use Drupal\dkan_harvest\File;
use Drupal\dkan_harvest\Stdout;

class Harvest {

  public $config;

  public $transform = [];

  public $load;

  public $log;

  public function __construct($config) {
    $this->config = $config;
  }

  function init($harvest) {
    $logClass = "Drupal\\dkan_harvest\\" . $this->config->log->type;
    $this->log = new $logClass($this->config->log->debug, $harvest->sourceId, $harvest->runId);
		$this->log->write('DEBUG', 'init', 'Initializing harvest');

    $extractClass = "Drupal\\dkan_harvest\\" . $harvest->source->type;
    $this->extract = new $extractClass($this->config, $harvest, $this->log);

    $this->transforms = $this->initializeTransforms($harvest->transforms);

    $loadClass = "Drupal\\dkan_harvest\\" . $harvest->load->type;
    $this->load = new $loadClass($this->load, $this->log);
  }

  function initializeTransforms($transforms) {
    $trans = array();
    foreach ($transforms as $transform) {
      if (is_Object($transform)) {
        $transform = (Array)$transform;
        $name = array_keys($transform)[0];
        $class = "Drupal\\dkan_harvest\\" . $name;
        $config = $transform[$name];
        $trans[] = new $class($config, $this->log);
      }
      else {
        $class = "Drupal\\dkan_harvest\\" . $transform;
        $trans[] = new $class(NULL, $this->log);
      }
    }
    return $trans;
  }

  function cache() {
    $this->extract->cache();
  }

  function extract() {
    $items = $this->extract->run();
    return $items;
  }

  function transform($items) {
    foreach ($this->transforms as $transform) {
      $transform->run($items);
    }
  }

  function load($items) {
    $this->load->run($items);
  }

}
