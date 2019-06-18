<?php

namespace Publish\Lib;

class Project
{
  public $name;
  public $path;

  private $_available = FALSE;

  private $_sourceDir;
  private $_remoteHost;
  private $_remotePath;

  private $_excludeInfo = '';
  private $_publishBefore = '';
  private $_publishAfter = '';

  private $_exeBefore = '';
  private $_exeAfter = '';

  public function __construct($name, $path)
  {
    $this->name = $name;
    $this->path = $path;
  }

  private function _loadInfo($extrasName)
  {
    $configFile = sprintf('%s/publish.json', $this->path);
    if(file_exists($configFile))
    {
      $info = file_get_contents($configFile);

      $extDic = array(
        '__DIR__' => $this->path
      );

      $extFile = sprintf('%s/publish.%s.json', $this->path, $extrasName);
      if(file_exists($extFile)) {
        $extInfo = file_get_contents($extFile);
        $dic = json_decode($extInfo, TRUE);
        if(is_array($dic))
        {
          $extDic = array_merge($extDic, $dic);
        }
      }

      $info = preg_replace_callback(
        '#\$\{(\w+)\}#',
        function ($matches) use ($extDic) {
          $key = $matches[1];
          if(isset($extDic[$key]))
          {
            return $extDic[$key];
          }
          return $matches[0];
        },
        $info
      );

      $dic = json_decode($info, TRUE);

      if(is_array($dic)) {
        $this->_sourceDir = $dic['source_dir'];
        $this->_remoteHost = $dic['remote']['host'];
        $this->_remotePath = $dic['remote']['target_dir'];

        if(isset($dic['ignores']) && is_array($dic['ignores']))
        {
          $arr = array();
          foreach ($dic['ignores'] as $item)
          {
            array_push($arr, sprintf('--exclude "%s"', $item));
          }

          $this->_excludeInfo = implode(' ', $arr);
        }

        if(isset($dic['remote']['publish_before']))
        {
          $items = $dic['remote']['publish_before'];
          if(is_array($items) && count($items) > 0)
          {
            $this->_publishBefore = implode('; ', $items);
          }
        }

        if(isset($dic['remote']['publish_after']))
        {
          $items = $dic['remote']['publish_after'];
          if(is_array($items) && count($items) > 0)
          {
            $this->_publishAfter = implode('; ', $items);
          }
        }

        if(isset($dic['exe_before']))
        {
          $items = $dic['exe_before'];
          if(is_array($items) && count($items) > 0)
          {
            $this->_exeBefore = implode('; ', $items);
          }
        }

        if(isset($dic['exe_after']))
        {
          $items = $dic['exe_after'];
          if(is_array($items) && count($items) > 0)
          {
            $this->_exeAfter = implode('; ', $items);
          }
        }

        $this->_available = TRUE;
      }
    }

    if (!$this->_available) {
      echo "parameters error.\n";
      exit;
    }
  }

  public function build($extrasName)
  {
    $this->_loadInfo($extrasName);

    $tmpl = file_get_contents(__DIR__ . '/../templates/publish.tmpl');

    $dic = array(
      'projectName' => $this->name,
      'sourceDir' => $this->_sourceDir,
      'remoteHost' => $this->_remoteHost,
      'remotePath' => $this->_remotePath,
      'excludeInfo' => $this->_excludeInfo,
      'publishBefore' => $this->_publishBefore,
      'publishAfter' => $this->_publishAfter,
      'exeBefore' => $this->_exeBefore,
      'exeAfter' => $this->_exeAfter,
    );

    $content = preg_replace_callback(
      '#\{\{\$(\w+)\}\}#',
      function ($matches) use ($dic) {
        $key = $matches[1];
        return $dic[$key];
      },
      $tmpl
    );

    $buildFile = sprintf('%s/../bin/build-%s.sh', __DIR__, $this->name);
    file_put_contents($buildFile, $content);
    chmod($buildFile, 0755);

    passthru($buildFile);
  }
}