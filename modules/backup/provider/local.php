﻿<?php
require_once("IProvider.php");

class LocalBackup implements IProvider
{
    public $error;
    
    function __construct($path, $logger)
    {
        $this->path = $path;
        $this->logger = $logger;
    }
    
    public function getFreeSpace()
    {
        return disk_free_space($this->path);
    }
    
    public function getList()
    {
        $pattern = $this->path."/*.";
        $pattern .= IsWindowsOS() ? 'tar' : 'tgz';
        $res = glob($pattern);
        if ($res) { 
            $files = array();
            foreach ($res as $filename) {
                $file = array();
                $file["NAME"] = basename($filename);
                $file["CREATED"] = date("Y-m-d H:i:s", filemtime($filename));
                $file["SIZE"] = filesize($filename);
                //$file["URL"] = $filename;
                $files[] = $file;
            }
            return $files;
        }
    }
    
    public function addBackup($file, $backup)
    {
        @copy($file, $this->path."/".$backup);
    }
    
    public function deleteBackup($backup)
    {
        $filename = $this->path ."/". $backup;
        echo $filename;
        unlink($filename);
    }
	

}