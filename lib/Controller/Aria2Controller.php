<?php
namespace OCA\NcDownloader\Controller;

use OCA\NcDownloader\Tools\Aria2;
use OCA\NcDownloader\Tools\DBConn;
use OCA\NcDownloader\Tools\File;
use OCA\NcDownloader\Tools\Helper;
use OCA\NcDownloader\Tools\Settings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OC_Util;
use \OC\Files\Filesystem;

class Aria2Controller extends Controller
{
    private $userId;
    private $settings = null;
    //@config OC\AppConfig
    private $config;
    private $aria2Opts;
    private $l10n;

    public function __construct($appName, IRequest $request, $UserId, IL10N $IL10N, IRootFolder $rootFolder, Aria2 $aria2)
    {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->uid = $UserId;
        $this->l10n = $IL10N;
        $this->rootFolder = $rootFolder;
        $this->urlGenerator = \OC::$server->getURLGenerator();
        $this->settings = new Settings($UserId);
        $this->downloadDir = $this->settings->get('ncd_downloader_dir') ?? "/Downloads";
        OC_Util::setupFS();
        //$this->config = \OC::$server->getAppConfig();

        $this->aria2 = $aria2;

        $this->aria2->init();
        $this->dbconn = new DBConn();
    }

    public function Action($path)
    {
        $path = strtolower(trim($path));

        if (!in_array($path, ['start', 'check']) && !($gid = $this->request->getParam('gid'))) {
            return new JSONResponse(['error' => "no gid value is received!"]);
        }
        switch (strtolower($path)) {
            case "check":
                $resp = $this->aria2->isRunning();
                break;
            case "start":
                $resp = $this->Start();
                break;
            case "pause":
                $resp = $this->aria2->pause($gid);
                break;
            case "remove":
                $resp = $this->aria2->remove($gid);
                break;
            case "unpause":
                $resp = $this->aria2->unpause($gid);
                break;
            case "get":
                $resp = $this->aria2->tellStatus($gid);
                break;
            case 'purge':
                $resp = $this->aria2->removeDownloadResult($gid);
        }
        return new JSONResponse($resp);
    }
    private function Start()
    {
        if ($this->aria2->isRunning()) {
            $data = ['status' => (bool) $this->aria2->stop()];
            return $data;
        }
        $data = $this->aria2->start();
        return $data;
    }
    public function Update()
    {
        File::syncFolder($this->downloadDir);
        return new JSONResponse([]);
    }

    private function createActionItem($name, $path)
    {
        return array(
            'name' => $name,
            'path' => $this->urlGenerator->linkToRoute('ncdownloader.Aria2.Action', ['path' => $path]),
        );
    }
    public function getStatus($path)
    {
        //$path = $this->request->getRequestUri();
        $counter = $this->aria2->getCounters();
        switch (strtolower($path)) {
            case "active":
                $resp = $this->aria2->tellActive();
                break;
            case "waiting":
                $resp = $this->aria2->tellWaiting([0, 999]);
                break;
            case "complete":
                $resp = $this->aria2->tellStopped([0, 999]);
                break;
            case "fail":
                $resp = $this->aria2->tellFail([0, 999]);
                break;
            default:
                $resp = $this->aria2->tellActive();
        }
        if (isset($resp['error'])) {
            return new JSONResponse($resp);
        }
        $data = $this->prepareResp($resp);
        $data['counter'] = $counter;
        return new JSONResponse($data);
    }
    private function prepareResp($resp)
    {

        $data = [];
        if (empty($resp)) {
            return $data;
        }
        $data['row'] = [];

        foreach ($resp as $value) {

            $gid = $value['following'] ?? $value['gid'];
            if ($row = $this->dbconn->getByGid($gid)) {
                $filename = $row['filename'];
                $timestamp = $row['timestamp'];
            } else if (isset($value['files'][0]['path'])) {
                $parts = explode("/", ($path = $value['files'][0]['path']));
                if (count($parts) > 1) {
                    $filename = basename(dirname($path));
                } else {
                    $filename = basename($path);
                }
            } else {
                $filename = "Unknown";
            }
            if (!isset($value['completedLength'])) {
                continue;
            }
            //internal nextcloud absolute path for nodeExists
            //$file = $this->userFolder . $this->downloadDir . "/" . $filename;
            // $dir = $this->rootFolder->nodeExists($file) ? $this->downloadDir . "/" . $filename : $this->downloadDir;
            $file = $this->downloadDir . "/" . $filename;
            $params = ['dir' => $this->downloadDir];
            $fileInfo = Filesystem::getFileInfo($file);
            if ($fileInfo) {
                $fileType = $fileInfo->getType();
                if ($fileType === "dir") {
                    $params = ['dir' => $file];
                }
            }
            $folderLink = $this->urlGenerator->linkToRoute('files.view.index', $params);
            //$peers = ($this->getPeers($info['gid']));
            $completed = Helper::formatBytes($value['completedLength']);
            $percentage = $value['completedLength'] ? 100 * ($value['completedLength'] / $value['totalLength']) : 0;
            $completed = Helper::formatBytes($value['completedLength']);

            $total = Helper::formatBytes($value['totalLength']);

            $remaining = (int) $value['totalLength'] - (int) $value['completedLength'];
            $remaining = ($value['downloadSpeed'] > 0) ? ($remaining / $value['downloadSpeed']) : 0;
            $left = Helper::formatInterval($remaining);

            $numSeeders = $value['numSeeders'] ?? 0;
            $extraInfo = "Seeders: $numSeeders";
            // $numPeers = isset($peers['result']) ? count($peers['result']) : 0;
            $value['progress'] = array(sprintf("%s(%.2f%%)", $completed, $percentage), $extraInfo);
            $timestamp = $timestamp ?? 0;
            //$prefix = $value['files'][0]['path'];
            $filename = sprintf('<a class="download-file-folder" href="%s">%s</a>', $folderLink, $filename);
            $fileInfo = sprintf("%s | %s", $total, date("Y-m-d H:i:s", $timestamp));

            $tmp = [];
            $actions = [];
            if ($this->aria2->methodName === "tellStopped") {
                $actions[] = $this->createActionItem('purge', 'purge');
            } else {
                $actions[] = $this->createActionItem('delete', 'remove');
            }
            if ($this->aria2->methodName === "tellWaiting") {
                $actions[] = $this->createActionItem('unpause', 'unpause');
            }
            $tmp['filename'] = array($filename, $fileInfo);
            if ($this->aria2->methodName === "tellActive") {
                $speed = [Helper::formatBytes($value['downloadSpeed']), $left . " left"];
                $tmp['speed'] = $speed;
                $tmp['progress'] = $value['progress'];
                $actions[] = $this->createActionItem('pause', 'pause');
            }
            if (strtolower($value['status']) === 'error') {
                $tmp['status'] = $value['errorMessage'];
            } else if ($this->aria2->methodName !== "tellActive") {
                $tmp['status'] = $value['status'];
            }
            $tmp['data_gid'] = $value['gid'] ?? 0;
            $tmp['actions'] = $actions;
            //$tmp['actions'] = '';
            array_push($data['row'], $tmp);
        }
        if ($this->aria2->methodName === "tellActive") {
            $data['title'] = Helper::getTableTitles('active');
        } else {
            $data['title'] = Helper::getTableTitles();
        }
        return $data;
    }
}