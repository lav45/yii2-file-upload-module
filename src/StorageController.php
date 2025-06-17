<?php

namespace lav45\fileUpload;

use League\Flysystem\StorageAttributes;
use Yii;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\helpers\Console;

/**
 * Class StorageController
 * @package lav45\fileUpload
 */
class StorageController extends Controller
{
    use FileSystemTrait;

    /** @var bool recursive remove folder */
    public $recursive = false;
    /** @var bool */
    public $force = false;
    /** @var int */
    public $older_than = 0;
    /** @var string */
    public $defaultAction = 'ls';

    /**
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'fs';
        if ($actionID === 'ls') {
            $options[] = 'recursive';
        }
        if ($actionID === 'scp') {
            $options[] = 'force';
        }
        if ($actionID === 'clear') {
            $options[] = 'recursive';
            $options[] = 'older_than';
            $options[] = 'force';
        }
        return $options;
    }

    /**
     * @return array
     */
    public function optionAliases()
    {
        $aliases = parent::optionAliases();
        $aliases['r'] = 'recursive';
        $aliases['f'] = 'force';
        $aliases['o'] = 'older_than';
        return $aliases;
    }

    /**
     * Show files in a folder
     *
     * @param string $directory
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLs($directory = '')
    {
        $items = $this->getFs()->listContents($directory, $this->recursive);
        $formatter = Yii::$app->getFormatter();
        foreach ($items as $item) {
            /** @var StorageAttributes $item */
            if ($item->lastModified() !== null) {
                echo $formatter->asDatetime($item->lastModified());
                echo "\t";
            }

            echo $item->isDir() ? 'D' : 'F';
            echo ' ';

            echo $this->recursive ? $item->path() : basename($item->path());
            echo "\n";
        }
    }

    /**
     * Move a file
     *
     * @param string $source
     * @param string $destination
     * @throws \yii\base\InvalidConfigException
     */
    public function actionMv($source, $destination)
    {
        $this->getFs()->move($source, $destination);
    }

    /**
     * Copy the file
     *
     * @param string $source
     * @param string $destination
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCp($source, $destination)
    {
        $this->getFs()->copy($source, $destination);
    }

    /**
     * Copy local file to the storage
     *
     * @param string $source
     * @param string $destination
     * @throws \yii\base\InvalidConfigException
     */
    public function actionScp($source, $destination)
    {
        $fs = $this->getFs();

        if (file_exists($source)) {
            if ($this->isDir($destination)) {
                $destination .= '/' . basename($source);
            }
            if ($this->force === false && $fs->has($destination)) {
                $this->stdout("{$destination} file exist\n", Console::FG_RED);
                return;
            }

            $stream = fopen($source, 'rb+');
            $fs->writeStream($destination, $stream);
            return;
        }

        if ($fs->has($source)) {
            if ($this->force === false && file_exists($destination)) {
                $this->stdout("{$destination} file exist\n", Console::FG_RED);
                return;
            }

            $stream = $fs->readStream($source);
            file_put_contents($destination, $stream);
            return;
        }

        $this->stdout("{$source} file not exist\n", BaseConsole::FG_RED);
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRm($path)
    {
        $fs = $this->getFs();

        if ($this->isDir($path)) {
            $fs->deleteDirectory($path);
        } else {
            $fs->delete($path);
        }
    }

    /**
     * @param string $path
     * @throws \yii\base\InvalidConfigException
     */
    protected function isDir($path): bool
    {
        return $this->getFs()->directoryExists($path);
    }

    /**
     * Remove all files
     *
     * @param string $path
     * @throws \yii\base\InvalidConfigException
     */
    public function actionClear($path = '')
    {
        $path = trim($path);
        if ($this->force === false &&
            (
                (empty($path) || $path === '/') ||
                $this->older_than === 0
            )
        ) {
            $this->stdout('WARNING', BaseConsole::FG_RED);
            $this->stdout(" If you want to delete all files, use the parameter -f (--force)\n");
            return;
        }

        $fs = $this->getFs();
        $list = $fs->listContents($path, $this->recursive);

        foreach ($list as $item) {
            if ($item instanceof \League\Flysystem\FileAttributes && (
                    (
                        $this->older_than === 0 &&
                        $this->force === true
                    ) || (
                        $item->lastModified() !== null &&
                        $this->older_than > 0 &&
                        strtotime("+{$this->older_than} day", $item->lastModified()) < time()
                    )
                )
            ) {
                $fs->delete($item->path());
            }
        }
    }
}