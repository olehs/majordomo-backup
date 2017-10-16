<?php
/**
* Backup 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 14:10:57 [Oct 12, 2017])
*/
//
//
class backup extends module {
/**
* backup
*
* Module class constructor
*
* @access private
*/
function backup() {
  $this->name="backup";
  $this->title="Backup";
  $this->module_category="<#LANG_SECTION_SYSTEM#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
    $this->getConfig();
    $out['PROVIDER'] = $this->config['PROVIDER']; 
    $out['LOCAL_PATH'] = $this->config['LOCAL_PATH'];
    if ($this->view_mode=='update_settings') {
        global $provider;
        $this->config['PROVIDER'] = $provider;
        global $local_path;
        $this->config['LOCAL_PATH'] = $local_path; 
        $this->saveConfig();
        $this->redirect("?");
    }
    if($this->view_mode == '') {
        $this->get_backups($out);
    }
    if($this->view_mode == 'delete_backup') {
        global $name;
        $this->delete_backup($name);
        $this->redirect("?"); 
    }
    if($this->view_mode == 'create_backup') {
        $this->create_backup();
        $this->redirect("?"); 
    }
            
 //foreach($temp_files as $file) {.............}
}

function get_backups(&$out) {
    $provider = $this->getProvider();
    $backups = $provider->getList();
    print_r($backups);
    //paging($backups, 100, $out); // search result paging
    $out["RESULT"] = $backups;
}

function delete_backup($name) {
  $provider = $this->getProvider();
  $provider->deleteBackup($name);  
 } 

function create_backup() {
    $provider = $this->getProvider();
    
    $file .= "backup";
    $file .= IsWindowsOS() ? '.tar' : '.tgz';
    
    if (mkdir(ROOT . 'backup/temp', 0777)) {
        $this->copyTree(ROOT . 'templates', ROOT . 'backup/temp/templates');
        $this->copyTree(ROOT . 'img', ROOT . 'backup/temp/img');
        $this->copyTree(ROOT . 'js', ROOT . 'backup/temp/js');
        $pt = array('\.css');
        $this->copyFiles(ROOT, ROOT . 'backup/temp', 0, $pt);
        $pt = array('\.swf');
        $this->copyFiles(ROOT, ROOT . 'backup/temp', 0, $pt);
        $pt = array('\.htc');
        $this->copyFiles(ROOT, ROOT . 'backup/temp', 0, $pt);

        $this->copyTree(ROOT . 'lib', ROOT . 'backup/temp/lib');
        $this->copyTree(ROOT . 'modules', ROOT . 'backup/temp/modules');
        $this->copyTree(ROOT . 'scripts', ROOT . 'backup/temp/scripts');
        $this->copyTree(ROOT . 'languages', ROOT . 'backup/temp/languages');
        $pt = array('\.php');
        $this->copyFiles(ROOT, ROOT . 'backup/temp', 0, $pt);
        @unlink(ROOT . 'backup/temp/config.php');
        $this->copyTree(ROOT . 'forum', ROOT . 'backup/temp/forum');
        @unlink(ROOT . 'backup/temp/forum/config.php');

        $this->copyTree(ROOT . 'cms', ROOT . 'backup/temp/cms');
        $this->backupdatabase(ROOT . 'backup/temp/dump.sql');
        
        if (IsWindowsOS()) {
            $result = exec('tar.exe --strip-components=2 -C ./backup/temp/ -cvf ./backup/' . $file . ' ./');
            $new_name = str_replace('.tar', '.tar.gz', $file);
            $result = exec('gzip.exe ./backup/' . $file);
            if (file_exists('./backup/' . $new_name)) {
                $file = $new_name;
            }
        } else {
            chdir(ROOT . 'backup/temp');
            exec('tar cvzf ../' . $file . ' .');
            chdir('../../');
        }
        $this->removeTree(ROOT . 'backup/temp');
    }
    
    $backupName .= "backup_" . date("Y-m-d H:i:s");
    $backupName .= IsWindowsOS() ? '.tar' : '.tgz';
    $file = ROOT . 'backup/'. $file;
    $provider->addBackup($file,$backupName);
    unlink($file);
}


function backupdatabase($filename)
    {
        if (defined('PATH_TO_MYSQLDUMP'))
            $pathToMysqlDump = PATH_TO_MYSQLDUMP;
        else
            $pathToMysqlDump = IsWindowsOS() ? SERVER_ROOT . "/server/mysql/bin/mysqldump" : "/usr/bin/mysqldump";

        exec($pathToMysqlDump . " --user=" . DB_USER . " --password=" . DB_PASSWORD . " --no-create-db --add-drop-table --databases " . DB_NAME . ">" . $filename);
    }
 
function copyTree($source, $destination, $over = 0, $patterns = 0)
    {


        $res = 1;

        //Remove last slash '/' in source and destination - slash was added when copy
        $source = preg_replace("#/$#", "", $source);
        $destination = preg_replace("#/$#", "", $destination);

        if (!Is_Dir($source)) {
            return 0; // cannot create destination path
        }

        if (!Is_Dir($destination)) {
            if (!mkdir($destination)) {
                return 0; // cannot create destination path
            }
        }


        if ($dir = @opendir($source)) {
            while (($file = readdir($dir)) !== false) {
                if (Is_Dir($source . "/" . $file) && ($file != '.') && ($file != '..')) {
                    $res = $this->copyTree($source . "/" . $file, $destination . "/" . $file, $over, $patterns);
                } elseif (Is_File($source . "/" . $file) && (!file_exists($destination . "/" . $file) || $over)) {
                    if (!is_array($patterns)) {
                        $ok_to_copy = 1;
                    } else {
                        $ok_to_copy = 0;
                        $total = count($patterns);
                        for ($i = 0; $i < $total; $i++) {
                            if (preg_match('/' . $patterns[$i] . '/is', $file)) {
                                $ok_to_copy = 1;
                            }
                        }
                    }
                    if ($ok_to_copy) {
                        $res = copy($source . "/" . $file, $destination . "/" . $file);
                    }
                }
            }
            closedir($dir);
        }
        return $res;

    }

    function copyFile($source, $destination)
    {
        $tmp = explode('/', $destination);
        $total = count($tmp);
        if ($total > 0) {
            $d = $tmp[0];
            for ($i = 1; $i < ($total - 1); $i++) {
                $d .= '/' . $tmp[$i];
                if (!is_dir($d)) {
                    mkdir($d);
                }
            }
        }
        return copy($source, $destination);

    }

    function copyFiles($source, $destination, $over = 0, $patterns = 0)
    {

        $res = 1;

        if (!Is_Dir($source)) {
            return 0; // cannot create destination path
        }

        if (!Is_Dir($destination)) {
            if (!mkdir($destination)) {
                return 0; // cannot create destination path
            }
        }


        if ($dir = @opendir($source)) {
            while (($file = readdir($dir)) !== false) {
                if (Is_Dir($source . "/" . $file) && ($file != '.') && ($file != '..')) {
                    //$res=$this->copyTree($source."/".$file, $destination."/".$file, $over, $patterns);
                } elseif (Is_File($source . "/" . $file) && (!file_exists($destination . "/" . $file) || $over)) {
                    if (!is_array($patterns)) {
                        $ok_to_copy = 1;
                    } else {
                        $ok_to_copy = 0;
                        $total = count($patterns);
                        for ($i = 0; $i < $total; $i++) {
                            if (preg_match('/' . $patterns[$i] . '/is', $file)) {
                                $ok_to_copy = 1;
                            }
                        }
                    }
                    if ($ok_to_copy) {
                        $res = copy($source . "/" . $file, $destination . "/" . $file);
                    }
                }
            }
            closedir($dir);
        }
        return $res;
    }
    
    function removeTree($destination, $iframe = 0)
    {

        $res = 1;

        if (!Is_Dir($destination)) {
            return 0; // cannot create destination path
        }
        if ($dir = @opendir($destination)) {

            if ($iframe) {
                $this->echonow("Removing dir $destination ... ");
            }


            while (($file = readdir($dir)) !== false) {
                if (Is_Dir($destination . "/" . $file) && ($file != '.') && ($file != '..')) {
                    $res = $this->removeTree($destination . "/" . $file);
                } elseif (Is_File($destination . "/" . $file)) {
                    $res = @unlink($destination . "/" . $file);
                }
            }
            closedir($dir);
            $res = @rmdir($destination);

            if ($iframe) {
                $this->echonow("OK<br/>", "green");
            }


        }
        return $res;
    }
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}

function getProvider() {
    $this->getConfig();
    switch ($this->config['PROVIDER']) {
            case 0: // local
                require_once("./modules/backup/provider/local.php");
                $provider = new LocalBackup($this->config['LOCAL_PATH']);
                break;
            case 1: // GDrive
                require_once("./modules/backup/provider/gdrive.php");
                $provider = new GdriveBackup();
                break;
    }
    return $provider;
}

/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgT2N0IDEyLCAyMDE3IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/