<?php

namespace Publish\Lib;

class PublishMaster
{
  private static $_instance = NULL;
  private $_projectMap;

  public function __construct()
  {
    $filePath = __DIR__ . '/../bin/_projects.json';
    if (file_exists($filePath)) {
      $content = file_get_contents($filePath);
      $this->_projectMap = json_decode($content, TRUE);
    } else {
      $this->_projectMap = [];
    }
  }

  public static function getInstance()
  {
    if(!(self::$_instance instanceof self)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __clone()
  {
    die('Clone is not allowed.' . E_USER_ERROR);
  }

  public function project($key)
  {
    $project = NULL;

    if(isset($this->_projectMap[$key])) {
      $data = $this->_projectMap[$key];
      $project = new Project($data['name'], $data['path']);
    }

    return $project;
  }
}
