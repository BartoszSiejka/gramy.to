<?php

/*
 * This file is part of the gramy.to (weplay.it) app.
 *
 * (c) Bartosz Siejka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use App\Entity\DatabaseVersion;
use ZipArchive;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DefaultController extends AbstractController
{
    public function index()
    {
        return $this->render('index.html.twig', array());
    }
    
    public function switchToDark(Request $request) 
    {
        $url = $_POST['url'];
        $expireDate = new \DateTime('now');
        $expireDate->modify('+200 years');
        $response = new Response("Dark Mode", 200);
        $cookies = $request->cookies;
        
        if ($cookies->has('dark_mode') && $cookies->get('dark_mode') == true) {
            $response->headers->setCookie(Cookie::create('dark_mode', false, $expireDate));
            $response->send();
        } else {
            $response->headers->setCookie(Cookie::create('dark_mode', true, $expireDate));
            $response->send();
        }
        
        return $this->redirect($url);
    }
    
    public function backup()
    {
        $em = $this->getDoctrine()->getManager();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $localSongDir = opendir('../backup/local/songs/');
        $localSongArray = array();
        $localSongLastDate = '';
        $localSetlistDir = opendir('../backup/local/setlists/');
        $localSetlistArray = array();
        $localSetlistLastDate = '';
        $localInstrumentDir = opendir('../backup/local/instruments/');
        $localInstrumentArray = array();
        $localInstrumentLastDate = '';
        $externalSongDir = opendir('../backup/external/songs/');
        $externalSongArray = array();
        $externalSongLastDate = '';
        $externalSetlistDir = opendir('../backup/external/setlists/');
        $externalSetlistArray = array();
        $externalSetlistLastDate = '';
        $externalInstrumentDir = opendir('../backup/external/instruments/');
        $externalInstrumentArray = array();
        $externalInstrumentLastDate = '';

        while ($file = readdir($localSongDir)) {
            if (is_file('../backup/local/songs/' . $file)) {
                $localSongArray[] = $file;
            }
        }
        
        sort($localSongArray);
        rsort($localSongArray);
        
        if (!empty($localSongArray)) {
            $localSongLastDate = new \DateTime(str_replace('.zip', '', $localSongArray[0]));
        }

        while ($file = readdir($localSetlistDir)) {
            if (is_file('../backup/local/setlists/' . $file)) {
                $localSetlistArray[] = $file;
            }
        }
        
        sort($localSetlistArray);
        rsort($localSetlistArray);
        
        if (!empty($localSetlistArray)) {
            $localSetlistLastDate = new \DateTime(str_replace('.zip', '', $localSetlistArray[0]));
        }

        while ($file = readdir($localInstrumentDir)) {
            if (is_file('../backup/local/instruments/' . $file)) {
                $localInstrumentArray[] = $file;
            }
        }
        
        sort($localInstrumentArray);
        rsort($localInstrumentArray);
        
        if (!empty($localInstrumentArray)) {
            $localInstrumentLastDate = new \DateTime(str_replace('.zip', '', $localInstrumentArray[0]));
        }

        while ($file = readdir($externalSongDir)) {
            if (is_file('../backup/external/songs/' . $file)) {
                $externalSongArray[] = $file;
            }
        }
        
        sort($externalSongArray);
        rsort($externalSongArray);
        
        if (!empty($externalSongArray)) {
            $externalSongLastDate = new \DateTime(str_replace('.zip', '', $externalSongArray[0]));
        }

        while ($file = readdir($externalSetlistDir)) {
            if (is_file('../backup/external/setlists/' . $file)) {
                $externalSetlistArray[] = $file;
            }
        }
        
        sort($externalSetlistArray);
        rsort($externalSetlistArray);
        
        if (!empty($externalSetlistArray)) {
            $externalSetlistLastDate = new \DateTime(str_replace('.zip', '', $externalSetlistArray[0]));
        }

        while ($file = readdir($externalInstrumentDir)) {
            if (is_file('../backup/external/instruments/' . $file)) {
                $externalInstrumentArray[] = $file;
            }
        }
        
        sort($externalInstrumentArray);
        rsort($externalInstrumentArray);
        
        if (!empty($externalInstrumentArray)) {
            $externalInstrumentLastDate = new \DateTime(str_replace('.zip', '', $externalInstrumentArray[0]));
        }
        
        return $this->render('backup.html.twig', array('databaseVersion' => $databaseVersion, 'localSongLastDate' => $localSongLastDate, 'localSetlistLastDate' => $localSetlistLastDate, 'localInstrumentLastDate' => $localInstrumentLastDate, 'externalSongLastDate' => $externalSongLastDate, 'externalSetlistLastDate' => $externalSetlistLastDate, 'externalInstrumentLastDate' => $externalInstrumentLastDate));
    }
    
    public function backupChoose() 
    {
        return $this->render('backupChoose.html.twig');
    }
    
    public function backupData(ParameterBagInterface $params, $database) 
    {
        $em = $this->getDoctrine()->getManager();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $databaseParams = $params->get('database');
        $databaseUser = $databaseParams['user'];
        $databasePassword = $databaseParams['password'];
        $databaseName = $databaseParams['name'];
        $maxFilesInDirectory = $params->get('maxFilesInBackupDirectory');
        
        if ($database == 1) {
            $pathdir = "uploads/songs/";
            $date = $databaseVersion->getSong()->format('YmdHis');
            $zipcreated = "../backup/local/songs/".$date.".zip";
            $exec = exec('cd uploads/songs && mysqldump -u'.$databaseUser.' -p'.$databasePassword.' '.$databaseName.' song song_data > songs.sql', $output, $return_val);
            $fileArray = array();

            if ($return_val == 0) {
                $createZip = $this->zipData($pathdir, $zipcreated);
                
                if ($createZip == false) {
                    return $this->render('admin/gramyto/backupFailed.html.twig');
                }
                
                exec('rm uploads/songs/songs.sql');
                
                $backupDir = opendir('../backup/local/songs/');
                
                while ($backupFile = readdir($backupDir)) {
                    if (is_file('../backup/local/songs/'.$backupFile)) {
                        $fileArray[] = $backupFile;
                    }
                }
                
                $this->removeTooMuchFiles($maxFilesInDirectory, '../backup/local/songs/', $fileArray);
                
                return $this->render('backupSuccess.html.twig');
            } else {
                return $this->render('backupFailed.html.twig');
            }
        } else {
            if ($database == 2) {
                $tables = 'setlist setlist_song';
                $fileName = 'setlists.sql';
                $pathDir = '../backup/local/setlists/';
                $date = $databaseVersion->getSetlist()->format('YmdHis');
            } elseif ($database == 3) {
                $tables = 'instrument';
                $fileName = 'instruments.sql';
                $pathDir = '../backup/local/instruments/';
                $date = $databaseVersion->getInstrument()->format('YmdHis');
            }
            $fileArray = array();
            
            $zipcreated = $pathDir.$date.".zip";
            $zip = new \ZipArchive();
            $exec = exec('cd ../backup && mysqldump -u'.$databaseUser.' -p'.$databasePassword.' '.$databaseName.' '.$tables.' > '.$fileName, $output, $return_val);

            if ($return_val == 0 && $zip->open($zipcreated, ZipArchive::CREATE) === TRUE) {
                $zip->addFile('../backup/'.$fileName, $fileName);

                $zip->close();
                exec('rm ../backup/'.$fileName);
                $backupDir = opendir($pathDir);
                
                while ($backupFile = readdir($backupDir)) {
                    if (is_file($pathDir.$backupFile)) {
                        $fileArray[] = $backupFile;
                    }
                }
                
                $this->removeTooMuchFiles($maxFilesInDirectory, $pathDir, $fileArray);
                
                return $this->render('backupSuccess.html.twig');
            } else {
                return $this->render('backupFailed.html.twig');
            }
        }
    }
    
    public function restoreChoose() 
    {
        $localSongPathDir = '../backup/local/songs/';
        $externalSongPathDir = '../backup/external/songs/';
        $localSongFileArray = array();
        $externalSongFileArray = array();
        $localSetlistPathDir = '../backup/local/setlists/';
        $externalSetlistPathDir = '../backup/external/setlists/';
        $localSetlistFileArray = array();
        $externalSetlistFileArray = array();
        $localInstrumentPathDir = '../backup/local/instruments/';
        $externalInstrumentPathDir = '../backup/external/instruments/';
        $localInstrumentFileArray = array();
        $externalInstrumentFileArray = array();
        
        $localSongDir = opendir($localSongPathDir);
                
        while ($localSongFile = readdir($localSongDir)) {
            if (is_file($localSongPathDir.$localSongFile)) {
                $localSongFileArray[] = array($localSongFile, new \DateTime(str_replace('.zip', '', $localSongFile)));
            }
        }
        
        rsort($localSongFileArray);
        
        $externalSongDir = opendir($externalSongPathDir);
                
        while ($externalSongFile = readdir($externalSongDir)) {
            if (is_file($externalSongPathDir.$externalSongFile)) {
                $externalSongFileArray[] = array($externalSongFile, new \DateTime(str_replace('.zip', '', $externalSongFile)));
            }
        }
        
        rsort($externalSongFileArray);
        
        $localSetlistDir = opendir($localSetlistPathDir);
                
        while ($localSetlistFile = readdir($localSetlistDir)) {
            if (is_file($localSetlistPathDir.$localSetlistFile)) {
                $localSetlistFileArray[] = array($localSetlistFile, new \DateTime(str_replace('.zip', '', $localSetlistFile)));
            }
        }
        
        rsort($localSetlistFileArray);
        
        $externalSetlistDir = opendir($externalSetlistPathDir);
                
        while ($externalSetlistFile = readdir($externalSetlistDir)) {
            if (is_file($externalSetlistPathDir.$externalSetlistFile)) {
                $externalSetlistFileArray[] = array($externalSetlistFile, new \DateTime(str_replace('.zip', '', $externalSetlistFile)));
            }
        }
        
        rsort($externalSetlistFileArray);
        
        $localInstrumentDir = opendir($localInstrumentPathDir);
                
        while ($localInstrumentFile = readdir($localInstrumentDir)) {
            if (is_file($localInstrumentPathDir.$localInstrumentFile)) {
                $localInstrumentFileArray[] = array($localInstrumentFile, new \DateTime(str_replace('.zip', '', $localInstrumentFile)));
            }
        }
        
        rsort($localInstrumentFileArray);
        
        $externalInstrumentDir = opendir($externalInstrumentPathDir);
                
        while ($externalInstrumentFile = readdir($externalInstrumentDir)) {
            if (is_file($externalInstrumentPathDir.$externalInstrumentFile)) {
                $externalInstrumentFileArray[] = array($externalInstrumentFile, new \DateTime(str_replace('.zip', '', $externalInstrumentFile)));
            }
        }
        
        rsort($externalInstrumentFileArray);
        
        $counter = max(array(count($localSongFileArray), count($externalSongFileArray), count($localSetlistFileArray), count($externalSetlistFileArray), count($localInstrumentFileArray), count($externalInstrumentFileArray)));
       
        return $this->render('restoreChoose.html.twig', array('localSongFileArray' => $localSongFileArray, 'externalSongFileArray' => $externalSongFileArray, 'localSetlistFileArray' => $localSetlistFileArray, 'externalSetlistFileArray' => $externalSetlistFileArray, 'localInstrumentFileArray' => $localInstrumentFileArray, 'externalInstrumentFileArray' => $externalInstrumentFileArray, 'counter' => $counter - 1));
    }
    
    public function restoreData(ParameterBagInterface $params, $path, $database, $fileName)
    {
        $em = $this->getDoctrine()->getManager();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $databaseParams = $params->get('database');
        $databaseUser = $databaseParams['user'];
        $databasePassword = $databaseParams['password'];
        $databaseName = $databaseParams['name'];
        $zip = new ZipArchive();
        
        if ($database == 1) {
            if ($zip->open('../backup/'.$path.'/songs/'.$fileName) === TRUE) {
                $zip->extractTo('uploads/songs/');
                $zip->close();
                exec('cd uploads/songs/ && rm -Rf .tmb .quarantine && mysql -u'.$databaseUser.' -p'.$databasePassword.' '.$databaseName.' < songs.sql && rm songs.sql', $output, $return_val);
                if ($return_val == 0) {
                    $databaseVersion->setSong(new \DateTime(str_replace('.zip', '', $fileName)));
                    $em->persist($databaseVersion);
                    $em->flush();
                    
                    return $this->render('restoreSuccess.html.twig');
                } else {
                    return $this->render('restoreFailed.html.twig');
                }
            } else {
                return $this->render('restoreFailed.html.twig');
            }
        } elseif ($database == 2) {
            if ($zip->open('../backup/'.$path.'/setlists/'.$fileName) === TRUE) {
                $zip->extractTo('../backup/'.$path.'/setlists/');
                $zip->close();
                exec('cd ../backup/'.$path.'/setlists/ && mysql -u'.$databaseUser.' -p'.$databasePassword.' '.$databaseName.' < setlists.sql && rm setlists.sql', $output, $return_val);
                if ($return_val == 0) {
                    $databaseVersion->setSetlist(new \DateTime(str_replace('.zip', '', $fileName)));
                    $em->persist($databaseVersion);
                    $em->flush();
                    
                    return $this->render('restoreSuccess.html.twig');
                } else {
                    return $this->render('restoreFailed.html.twig');
                }
            } else {
                return $this->render('restoreFailed.html.twig');
            }
        } elseif ($database == 3) {
            if ($zip->open('../backup/'.$path.'/instruments/'.$fileName) === TRUE) {
                $zip->extractTo('../backup/'.$path.'/instruments/');
                $zip->close();
                exec('cd ../backup/'.$path.'/instruments/ && mysql -u'.$databaseUser.' -p'.$databasePassword.' '.$databaseName.' < instruments.sql && rm instruments.sql', $output, $return_val);
                if ($return_val == 0) {
                    $databaseVersion->setInstrument(new \DateTime(str_replace('.zip', '', $fileName)));
                    $em->persist($databaseVersion);
                    $em->flush();
                    
                    return $this->render('restoreSuccess.html.twig');
                } else {
                    return $this->render('restoreFailed.html.twig');
                }
            } else {
                return $this->render('restoreFailed.html.twig');
            }
        }
    }
    
    /* 
     * Nedd have php-ssh2 function install
     */
    public function sendBackup(ParameterBagInterface $params, TranslatorInterface $translator)
    {
        $sshParams = $params->get('ssh');
        $host = $sshParams['host'];
        $port = $sshParams['port'];
        $username = $sshParams['user'];
        $password = $sshParams['password'];
        $remoteDir = $sshParams['remoteDir'];
        $maxFilesInDirectory = $params->get('maxFilesInBackupDirectory');
        $localDir = '../backup/local/';

        if (!function_exists("ssh2_connect")) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.ssh2_connect')));
        }
        
        if (!$connection = ssh2_connect($host, $port)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.connection')));
        }

        if (!ssh2_auth_password($connection, $username, $password)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.authentication')));
        }

        if (!$stream = ssh2_sftp($connection)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.sftp')));
        }


        if (!$local = opendir($localDir)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
        }

        $filesArray = array();
        
        while (false !== ($file = readdir($local))) {
            if ($file == "." || $file == ".." || $file == ".gnupg") {
                continue;
            }
            
            if (!is_file($file)) {
                if (!$anotherDir = opendir($localDir.$file)) {
                    return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
                }

                while (false !== ($anotherFile = readdir($anotherDir))) {
                    if ($anotherFile == "." || $anotherFile == ".." || $anotherFile == ".gnupg") {
                        continue;
                    }
                    
                    $filesArray[$file][] = $anotherFile;
                }
            } else {
                $filesArray[] = $file;
            }
        }
        
        $content = '';
        $error = false;
        
        foreach ($filesArray as $key => $files) {
            $handle = opendir("ssh2.sftp://{$stream}{$remoteDir}{$key}");
            $filesInDirectory = array();
            
            while (false != ($uploadedFiles = readdir($handle))){
                if ($uploadedFiles == "." || $uploadedFiles == ".." || $uploadedFiles == ".gnupg") {
                    continue;
                }
                
                $filesInDirectory[] = $uploadedFiles;
            }
            
            foreach ($files as $file) { 
                if (in_array($file, $filesInDirectory)) {
                    $content .= $translator->trans('backup.remote.send.exist.remote') . ' ' . $key . '/' . $file . ' <br>';
                } else {
                    $content .= $translator->trans('backup.remote.send.copying') . ' ' . $key . '/' . $file . ' <br>';
                    if (!$local = @fopen($localDir . $key . '/' . $file, 'r')) {
                        $content .= $translator->trans('backup.remote.error.local.file.open') . ' ' . $key . '/' . $file . '<br>';
                        $error = true;
                        continue;
                    }

                    if (!$remote = @fopen("ssh2.sftp://{$stream}{$remoteDir}{$key}/{$file}", 'w')) {
                        try {
                            ssh2_sftp_mkdir($stream, $remoteDir.$key);

                            if (!$remote = @fopen("ssh2.sftp://{$stream}{$remoteDir}{$key}/{$file}", 'w')) {
                                $error = true;
                                $content .= $translator->trans('backup.remote.error.remote.file.create') . ' ' . $key . '/' . $file . '<br>';
                                continue;
                            }
                        } catch (Exception $ex) {
                            $error = true;
                            $content .= $translator->trans('backup.remote.error.remote.file.create') . ' ' . $key . '/' . $file . '<br>';
                            continue;
                        }
                    }

                    $read = 0;
                    $filesize = filesize($localDir . $key . '/' . $file);

                    while ($read < $filesize && ($buffer = fread($local, $filesize - $read))) {
                        $read += strlen($buffer);
                        if (fwrite($remote, $buffer) === FALSE) {
                            $content .= $translator->trans('backup.remote.error.remote.file.write') . ' ' . $key . '/' . $file . '<br>';

                            return $this->render('backupSendFailed.html.twig', array('content' => $content));
                        }
                    }

                    fclose($local);
                    fclose($remote);
                }
            }
            
            $handle = opendir("ssh2.sftp://{$stream}{$remoteDir}{$key}");
            $filesInDirectory = array();
            
            while (false != ($uploadedFiles = readdir($handle))){
                if ($uploadedFiles == "." || $uploadedFiles == ".." || $uploadedFiles == ".gnupg") {
                    continue;
                }
                
                $filesInDirectory[] = $uploadedFiles;
            }
            
            $this->removeRemoteTooMuchFiles($maxFilesInDirectory, $stream, $remoteDir.$key.'/', $filesInDirectory);
        }
        
        $connection = null;
        unset($connection);
        
        if ($error) {
            return $this->render('backupSendFailed.html.twig', array('content' => $content));
        } else {
            return $this->render('backupSendSuccess.html.twig', array('content' => $content));
        }
    }
    
    public function downloadBackup(ParameterBagInterface $params, TranslatorInterface $translator)
    {
        $sshParams = $params->get('ssh');
        $host = $sshParams['host'];
        $port = $sshParams['port'];
        $username = $sshParams['user'];
        $password = $sshParams['password'];
        $remoteDir = $sshParams['remoteDir'];
        $anotherRemoteDir = $sshParams['anotherRemoteDir'];
        $maxFilesInDirectory = $params->get('maxFilesInBackupDirectory');
        $localDir = '../backup/local/';
        $localExternalDir = '../backup/external/';
        $content = '';

        if (!function_exists("ssh2_connect")) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.ssh2_connect')));
        }

        if (!$connection = ssh2_connect($host, $port)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.connection')));
        }
        
        if (!ssh2_auth_password($connection, $username, $password)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.authentication')));
        }
        
        if (!$stream = ssh2_sftp($connection)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.sftp')));
        }

        if (!$dirRemote = opendir("ssh2.sftp://{$stream}{$remoteDir}")) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
        }

        if ($anotherRemoteDir && !$dirAnotherRemote = opendir("ssh2.sftp://{$stream}{$anotherRemoteDir}")) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
        }

        if ($anotherRemoteDir) {
            $filesArray = array();

            while (false !== ($file = readdir($dirAnotherRemote))) {
                if ($file == "." || $file == ".." || $file == ".gnupg") {
                    continue;
                }

                if (!is_file($file)) {
                    if (!$anotherDir = opendir('ssh2.sftp://'.$stream.$anotherRemoteDir.$file)) {
                        return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
                    }

                    while (false !== ($anotherFile = readdir($anotherDir))) {
                        if ($anotherFile == "." || $anotherFile == ".." || $anotherFile == ".gnupg") {
                            continue;
                        }

                        $filesArray[$file][] = $anotherFile;
                    }
                } else {
                    $filesArray[] = $file;
                }
            }

            $error = false;

            foreach ($filesArray as $key => $files) {
                $handle = opendir($localExternalDir.$key);
                $filesInDirectory = array();

                while (false != ($downloadedFiles = readdir($handle))){
                    if ($downloadedFiles == "." || $downloadedFiles == ".." || $downloadedFiles == ".gnupg") {
                        continue;
                    }

                    $filesInDirectory[] = $downloadedFiles;
                }
                
                foreach ($files as $file) {
                    if (in_array($file, $filesInDirectory)) {
                        $content .= $translator->trans('backup.remote.send.exist.local') . ' ' . $key . '/' . $file . ' <br>';
                    } else {
                        $content .= $translator->trans('backup.remote.send.copying') . ' ' . $key . '/' . $file . ' <br>';

                        if (!$remote = @fopen("ssh2.sftp://{$stream}/{$anotherRemoteDir}{$key}/{$file}", 'r')) {
                            $content .= $translator->trans('backup.remote.error.remote.file.open') . ' ' . $key . '/' . $file . '<br>';
                            $error = true;
                            continue;
                        }

                        if (!$localExternal = @fopen($localExternalDir . $key . '/' . $file, 'w')) {
                            try {
                                mkdir($localExternalDir.$key);

                                if (!$localExternal = @fopen($localExternalDir . $key . '/' . $file , 'w')) {
                                    $error = true;
                                    $content .= $translator->trans('backup.remote.error.remote.file.create') . ' ' . $key . '/' . $file . '<br>';
                                    continue;
                                }
                            } catch (Exception $ex) {
                                $error = true;
                                $content .= $translator->trans('backup.remote.error.remote.file.create') . ' ' . $key . '/' . $file . '<br>';
                                continue;
                            }
                        }

                        $read = 0;
                        $filesize = filesize("ssh2.sftp://{$stream}/{$anotherRemoteDir}{$key}/{$file}");
                        while ($read < $filesize && ($buffer = fread($remote, $filesize - $read))) {
                            $read += strlen($buffer);
                            if (fwrite($localExternal, $buffer) === FALSE) {
                                $content .= $translator->trans('backup.remote.error.local.file.write') . ' ' . $key . '/' . $file . '<br>';

                                return $this->render('backupSendFailed.html.twig', array('content' => $content));
                            }
                        }

                        fclose($localExternal);
                        fclose($remote);
                    }
                } 

                $handle = opendir($localExternalDir.$key);
                $filesInDirectory = array();

                while (false != ($downloadedFiles = readdir($handle))){
                    if ($downloadedFiles == "." || $downloadedFiles == ".." || $downloadedFiles == ".gnupg") {
                        continue;
                    }

                    $filesInDirectory[] = $downloadedFiles;
                }

                $this->removeTooMuchFiles($maxFilesInDirectory, $localExternalDir.$key.'/', $filesInDirectory);
            }
        }

        $filesArray = array();
        
        while (false !== ($file = readdir($dirRemote))) {
            if ($file == "." || $file == ".." || $file == ".gnupg") {
                continue;
            }
            
            if (!is_file($file)) {
                if (!$anotherDir = opendir('ssh2.sftp://'.$stream.$remoteDir.$file)) {
                    return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
                }

                while (false !== ($anotherFile = readdir($anotherDir))) {
                    if ($anotherFile == "." || $anotherFile == ".." || $anotherFile == ".gnupg") {
                        continue;
                    }
                    
                    $filesArray[$file][] = $anotherFile;
                }
            } else {
                $filesArray[] = $file;
            }
        }
        
        $error = false;

        foreach ($filesArray as $key => $files) {
            $handle = opendir($localDir.$key);
            $filesInDirectory = array();
            
            while (false != ($downloadedFiles = readdir($handle))){
                if ($downloadedFiles == "." || $downloadedFiles == ".." || $downloadedFiles == ".gnupg") {
                    continue;
                }
                
                $filesInDirectory[] = $downloadedFiles;
            }
            
            foreach ($files as $file) {
                if (in_array($file, $filesInDirectory)) {
                    $content .= $translator->trans('backup.remote.send.exist.local') . ' ' . $key . '/' . $file . ' <br>';
                } else {
                    $content .= $translator->trans('backup.remote.send.copying') . ' ' . $key . '/' . $file . ' <br>';

                    if (!$remote = @fopen("ssh2.sftp://{$stream}/{$remoteDir}{$key}/{$file}", 'r')) {
                        $content .= $translator->trans('backup.remote.error.remote.file.open') . ' ' . $key . '/' . $file . '<br>';
                        $error = true;
                        continue;
                    }

                    if (!$local = @fopen($localDir . $key . '/' . $file, 'w')) {
                        try {
                            mkdir($localDir.$key);

                            if (!$local = @fopen($localDir . $key . '/' . $file , 'w')) {
                                $error = true;
                                $content .= $translator->trans('backup.remote.error.local.file.create') . ' ' . $key . '/' . $file . '<br>';
                                continue;
                            }
                        } catch (Exception $ex) {
                            $error = true;
                            $content .= $translator->trans('backup.remote.error.local.file.create') . ' ' . $key . '/' . $file . '<br>';
                            continue;
                        }
                    }

                    $read = 0;
                    $filesize = filesize("ssh2.sftp://{$stream}/{$remoteDir}{$key}/{$file}");
                    while ($read < $filesize && ($buffer = fread($remote, $filesize - $read))) {
                        $read += strlen($buffer);
                        if (fwrite($local, $buffer) === FALSE) {
                            $content .= $translator->trans('backup.remote.error.local.file.write') . ' ' . $key . '/' . $file . '<br>';

                            return $this->render('backupSendFailed.html.twig', array('content' => $content));
                        }
                    }

                    fclose($local);
                    fclose($remote);
                }
            }
            
            $handle = opendir($localDir.$key);
            $filesInDirectory = array();
            
            while (false != ($downloadedFiles = readdir($handle))){
                if ($downloadedFiles == "." || $downloadedFiles == ".." || $downloadedFiles == ".gnupg") {
                    continue;
                }
                
                $filesInDirectory[] = $downloadedFiles;
            }
            
            $this->removeTooMuchFiles($maxFilesInDirectory, $localDir.$key.'/', $filesInDirectory);
        }
        
        $connection = null;
        unset($connection);
        
        if ($error) {
            return $this->render('backupSendFailed.html.twig', array('content' => $content));
        } else {
            return $this->render('backupSendSuccess.html.twig', array('content' => $content));
        }
    }
    
    /* 
     * Need to have automount usb flash in system
     * Need to add 755 for media/user and 777 for mounted
     */
    public function sendBackupUSB(ParameterBagInterface $params, TranslatorInterface $translator)
    {
        $usbParams = $params->get('usb');
        $maxFilesToSend = $usbParams['maxFilesToSend'];
        $directoryName = $usbParams['directoryName'];
        $mountDirectory = $usbParams['mountDirectory'];
        $localDir = '../backup/local/';
        $targetUSB = array();
        $error = false;
        $content = '';
        $deviceError = 0;
        exec('lsblk -P', $lsblk);
        
        foreach($lsblk as $device) {
            $startPosition = strpos($device, 'MOUNTPOINT') + 12;
            $endPosition = strpos($device, '"', $startPosition);
            $mountedDevice = str_replace('\040', ' ', substr($device, $startPosition, $endPosition - $startPosition));
            
            if ($mountedDevice && strpos($mountedDevice, $mountDirectory) !== false) {
                $targetUSB[] = $mountedDevice;
            }
        }
        
        if (empty($targetUSB)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.usb.notFound')));
        }
        
        foreach ($targetUSB as $target) {
            if (!is_dir($target)) {
                $deviceError = $deviceError + 1;
                $content .= '<span style="color: #f00">'.$translator->trans('backup.remote.error.usb.device').' '.$target.$translator->trans('backup.remote.error.usb.permission').'</span><br>';
            }
            
            if (!is_dir($target.'/'.$directoryName)) {
                mkdir($target.'/'.$directoryName);
            }
            
            if (!is_dir($target.'/'.$directoryName.'/instruments')) {
                mkdir($target.'/'.$directoryName.'/instruments');
            }
            
            if (!is_dir($target.'/'.$directoryName.'/setlists')) {
                mkdir($target.'/'.$directoryName.'/setlists');
            }
            
            if (!is_dir($target.'/'.$directoryName.'/songs')) {
                mkdir($target.'/'.$directoryName.'/songs');
            }
        } 
        
        if ($deviceError >= count($targetUSB)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $content));
        }
        
        if (!$local = opendir($localDir)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
        }

        $filesArray = array();
        
        while (false !== ($file = readdir($local))) {
            if ($file == "." || $file == ".." || $file == ".gnupg") {
                continue;
            }
            
            if (!is_file($file)) {
                if (!$anotherDir = opendir($localDir.$file)) {
                    return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
                }

                while (false !== ($anotherFile = readdir($anotherDir))) {
                    if ($anotherFile == "." || $anotherFile == ".." || $anotherFile == ".gnupg") {
                        continue;
                    }
                    
                    $filesArray[$file][] = $anotherFile;
                }
            } else {
                $filesArray[] = $file;
            }
        }
        
        foreach ($filesArray as $key => $files) {
            if (count($files) > $maxFilesToSend) {
                sort($files);
                rsort($files);
                $tempFiles = array();
                
                for ($i = 0; $i < $maxFilesToSend; $i++) {
                    $tempFiles[] = $files[$i];
                } 
                
                $files = $tempFiles;
            }
            
            foreach ($targetUSB as $target) {
                $handle = opendir($target.'/'.$directoryName.'/'.$key);
                $filesInDirectory = array();

                while (false != ($uploadedFiles = readdir($handle))){
                    if ($uploadedFiles == "." || $uploadedFiles == ".." || $uploadedFiles == ".gnupg") {
                        continue;
                    }

                    $filesInDirectory[] = $uploadedFiles;
                }

                foreach ($files as $file) { 
                    if (in_array($file, $filesInDirectory)) {
                        $content .= $translator->trans('backup.remote.send.exist.usb') . ' ' . $key . '/' . $file . ' <br>';
                    } else {
                        $content .= $translator->trans('backup.remote.send.copying') . ' ' . $key . '/' . $file . ' <br>';
                        if (!$local = @fopen($localDir . $key . '/' . $file, 'r')) {
                            $content .= $translator->trans('backup.remote.error.local.file.open') . ' ' . $key . '/' . $file . '<br>';
                            $error = true;
                            continue;
                        }
                        
                        if (!$remote = @fopen($target.'/'.$directoryName.'/'.$key.'/'.$file, 'w')) {
                            $error = true;
                            $content .= $translator->trans('backup.remote.error.usb.file.create') . ' ' . $key . '/' . $file . '<br>';
                            continue;
                        }

                        $read = 0;
                        $filesize = filesize($localDir . $key . '/' . $file);

                        while ($read < $filesize && ($buffer = fread($local, $filesize - $read))) {
                            $read += strlen($buffer);
                            if (fwrite($remote, $buffer) === FALSE) {
                                $content .= $translator->trans('backup.remote.error.usb.file.write') . ' ' . $key . '/' . $file . '<br>';

                                return $this->render('backupSendFailed.html.twig', array('content' => $content));
                            }
                        }

                        fclose($local);
                        fclose($remote);
                    }
                }

                $handle = opendir($target.'/'.$directoryName.'/'.$key);
                $filesInDirectory = array();

                while (false != ($uploadedFiles = readdir($handle))){
                    if ($uploadedFiles == "." || $uploadedFiles == ".." || $uploadedFiles == ".gnupg") {
                        continue;
                    }

                    $filesInDirectory[] = $uploadedFiles;
                }

                $this->removeTooMuchFiles($maxFilesToSend, $target.'/'.$directoryName.'/'.$key.'/', $filesInDirectory);
            }
        }
        
        if ($error) {
            return $this->render('backupSendFailed.html.twig', array('content' => $content));
        } else {
            return $this->render('backupSendSuccess.html.twig', array('content' => $content));
        }
    }
    
    public function downloadBackupUSB(ParameterBagInterface $params, TranslatorInterface $translator)
    {
        $usbParams = $params->get('usb');
        $directoryName = $usbParams['directoryName'];
        $localDir = '../backup/local/';
        $mountDirectory = $usbParams['mountDirectory'];
        $targetUSB = array();
        $error = false;
        $content = '';
        $deviceError = 0;
        $maxFilesInDirectory = $params->get('maxFilesInBackupDirectory');
        exec('lsblk -P', $lsblk);
        
        foreach($lsblk as $device) {
            $startPosition = strpos($device, 'MOUNTPOINT') + 12;
            $endPosition = strpos($device, '"', $startPosition);
            $mountedDevice = str_replace('\040', ' ', substr($device, $startPosition, $endPosition - $startPosition));
            
            if ($mountedDevice && strpos($mountedDevice, $mountDirectory) !== false) {
                $targetUSB[] = $mountedDevice;
            }
        }
        
        if (empty($targetUSB)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.usb.notFound')));
        }
        
        foreach ($targetUSB as $target) {
            if (!is_dir($target) || !opendir($target)) {
                $deviceError = $deviceError + 1;
                $content .= '<span style="color: #f00">'.$translator->trans('backup.remote.error.usb.device').' '.$target.$translator->trans('backup.remote.error.usb.permission').'</span><br>';
            }
            
            if (!is_dir($target.'/'.$directoryName)) {
                $deviceError = $deviceError + 1;
                $content .= $translator->trans('backup.remote.error.usb.backupNotExist').' '.$target.'.';
            }
        } 
        
        if ($deviceError >= count($targetUSB)) {
            return $this->render('backupSendFailed.html.twig', array('content' => $content));
        }

        foreach ($targetUSB as $target) {
            $filesArray = array();
            $remoteDir = $target.'/'.$directoryName;
            $openRemoteDir = opendir($remoteDir);
            
            while (false !== ($file = readdir($openRemoteDir))) {
                if ($file == "." || $file == ".." || $file == ".gnupg") {
                    continue;
                }

                if (!is_file($file)) {
                    if (!$anotherDir = opendir($remoteDir.'/'.$file)) {
                        return $this->render('backupSendFailed.html.twig', array('content' => $translator->trans('backup.remote.error.directory')));
                    }

                    while (false !== ($anotherFile = readdir($anotherDir))) {
                        if ($anotherFile == "." || $anotherFile == ".." || $anotherFile == ".gnupg") {
                            continue;
                        }

                        $filesArray[$file][] = $anotherFile;
                    }
                } else {
                    $filesArray[] = $file;
                }
            }

            $error = false;

            foreach ($filesArray as $key => $files) {
                $handle = opendir($localDir.$key);
                $filesInDirectory = array();

                while (false != ($downloadedFiles = readdir($handle))){
                    if ($downloadedFiles == "." || $downloadedFiles == ".." || $downloadedFiles == ".gnupg") {
                        continue;
                    }

                    $filesInDirectory[] = $downloadedFiles;
                }

                foreach ($files as $file) {
                    if (in_array($file, $filesInDirectory)) {
                        $content .= $translator->trans('backup.remote.send.exist.local') . ' ' . $key . '/' . $file . ' <br>';
                    } else {
                        $content .= $translator->trans('backup.remote.send.copying') . ' ' . $key . '/' . $file . ' <br>';

                        if (!$remote = @fopen($remoteDir.'/'.$key.'/'.$file, 'r')) {
                            $content .= $translator->trans('backup.remote.error.usb.file.open') . ' ' . $key . '/' . $file . '<br>';
                            $error = true;
                            continue;
                        }

                        if (!$local = @fopen($localDir . $key . '/' . $file, 'w')) {
                            try {
                                mkdir($localDir.$key);

                                if (!$local = @fopen($localDir . $key . '/' . $file , 'w')) {
                                    $error = true;
                                    $content .= $translator->trans('backup.remote.error.local.file.create') . ' ' . $key . '/' . $file . '<br>';
                                    continue;
                                }
                            } catch (Exception $ex) {
                                $error = true;
                                $content .= $translator->trans('backup.remote.error.local.file.create') . ' ' . $key . '/' . $file . '<br>';
                                continue;
                            }
                        }

                        $read = 0;
                        $filesize = filesize($remoteDir.'/'.$key.'/'.$file);
                        while ($read < $filesize && ($buffer = fread($remote, $filesize - $read))) {
                            $read += strlen($buffer);
                            if (fwrite($local, $buffer) === FALSE) {
                                $content .= $translator->trans('backup.remote.error.local.file.write') . ' ' . $key . '/' . $file . '<br>';

                                return $this->render('backupSendFailed.html.twig', array('content' => $content));
                            }
                        }

                        fclose($local);
                        fclose($remote);
                    }
                }

                $handle = opendir($localDir.$key);
                $filesInDirectory = array();

                while (false != ($downloadedFiles = readdir($handle))){
                    if ($downloadedFiles == "." || $downloadedFiles == ".." || $downloadedFiles == ".gnupg") {
                        continue;
                    }

                    $filesInDirectory[] = $downloadedFiles;
                }

                $this->removeTooMuchFiles($maxFilesInDirectory, $localDir.$key.'/', $filesInDirectory);
            }
        }
        
        if ($error) {
            return $this->render('backupSendFailed.html.twig', array('content' => $content));
        } else {
            return $this->render('backupSendSuccess.html.twig', array('content' => $content));
        }
    }
    
    public function manual() 
    {
        return $this->render('manual.html.twig');
    }

    private function removeRemoteTooMuchFiles($maxFilesInDirectory, $stream, $path, $fileArray) 
    {
        if (count($fileArray) > $maxFilesInDirectory) {
            sort($fileArray);
            ssh2_sftp_unlink($stream, $path.$fileArray[0]);
            unset($fileArray[0]);
            $newFileArray  = array_values($fileArray);
            $this->removeTooMuchFiles($maxFilesInDirectory, $path, $newFileArray);
        }
    }
    
    private function removeTooMuchFiles($maxFilesInDirectory, $path, $fileArray) 
    {
        if (count($fileArray) > $maxFilesInDirectory) {
            sort($fileArray);
            unlink($path.$fileArray[0]);
            unset($fileArray[0]);
            $newFileArray  = array_values($fileArray);
            $this->removeTooMuchFiles($maxFilesInDirectory, $path, $newFileArray);
        }
    }
    
    private function zipData($source, $destination) {
        if (extension_loaded('zip') === true) {
            if (file_exists($source) === true) {
                $zip = new ZipArchive();

                if ($zip->open($destination, ZIPARCHIVE::CREATE) === true) {
                    $source = realpath($source);
                    
                    if (is_dir($source) === true) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
                        
                        foreach ($files as $file) {
                            if ($file->getFileName() !== '..' && $file->getFileName() !== '.') {
                                $file = realpath($file);

                                if (is_dir($file) === true) {
                                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                                } else if (is_file($file) === true) {
                                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                                }
                            }
                        }
                    } else if (is_file($source) === true) {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }
                return $zip->close();
            }
        }
        return false;
    }
}
