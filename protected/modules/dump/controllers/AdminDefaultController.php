<?php

namespace app\modules\dump\controllers;

use app\modules\dump\helpers\BaseDump;
use app\modules\dump\helpers\DumpInterface;
use Yii;
use yii\base\Exception as YiiException;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminDefaultController extends Controller
{
    public $layout = '@app/views/layouts/admin';

    /**
     * @return array|array[]
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['dump.openAdminPanel'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
                    'delete' => ['post'],
                    'delete-all' => ['post'],
                    'download' => ['post'],
                    'restore' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @return string
     * @throws YiiException
     */
    public function actionIndex(): string
    {
        $fileList = BaseDump::getFilesList();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $fileList,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return Response
     * @throws HttpException
     * @throws YiiException
     * @throws InvalidConfigException
     */
    public function actionCreate(): Response
    {
        $dbInfo = BaseDump::getDbInfo();

        $dumpFile = BaseDump::makePath($dbInfo['dbName']);

        /** @var DumpInterface $manager */
        $manager = $dbInfo['manager'];
        $command = $manager::makeDumpCommand($dumpFile, $dbInfo);

        Yii::debug([$dumpFile, $command], static::class);

        static::runProcess($command);

        return $this->redirect(['index']);
    }

    /**
     * @param array $command
     * @param bool $isRestore
     */
    protected static function runProcess($command, $isRestore = false): void
    {
        $descriptorspec = [
            ['pipe', 'r'], // STDIN
            ['pipe', 'w'], // STDOUT
            ['pipe', 'w'], // STDERR
        ];
        $command_nw = implode(' ', $command);

        $process = proc_open($command_nw, $descriptorspec, $pipes);
//        $output = stream_get_contents($pipes[1]);
        $output_err = stream_get_contents($pipes[2]);
        $return_value = proc_close($process);
        if ($return_value === 0) {
            $msg = !$isRestore ? Yii::t('app', 'Dump successfully created.') : Yii::t('app', 'Dump successfully restored.');
            Yii::$app->session->addFlash('success', $msg);
        } else {
            $msg = !$isRestore ? Yii::t('app', 'Dump failed.') : Yii::t('app', 'Restore failed.');
            $commandTxt = implode(' ', $command);
            Yii::$app->session->addFlash('error', $msg . '<br>' . 'Command - ' . $commandTxt . '<br>' . $output_err . $return_value);
            Yii::error($msg . PHP_EOL . 'Command - ' . $commandTxt . PHP_EOL . $output_err . PHP_EOL . $return_value);
        }
    }

    /**
     * @param string $id Name File Dump
     * @return Response
     * @throws HttpException
     * @throws NotFoundHttpException
     * @throws YiiException
     * @throws InvalidConfigException
     */
    public function actionRestore($id): Response
    {
        $dbInfo = BaseDump::getDbInfo();

        static::testFileName($id);

        $dumpFile = BaseDump::getPath() . DIRECTORY_SEPARATOR . $id;

        /** @var DumpInterface $manager */
        $manager = $dbInfo['manager'];
        $command = $manager::makeRestoreCommand($dumpFile, $dbInfo);

        Yii::debug([$dumpFile, $command], static::class);

        static::runProcess($command, true);

        return $this->redirect(['index']);
    }

    /**
     * @param string $fileName Name File Dump
     * @throws NotFoundHttpException
     * @throws YiiException
     */
    public static function testFileName($fileName): void
    {
        $fileList = BaseDump::getFilesList();
        $in_array = false;
        foreach ($fileList as $file) {
            if ($fileName === $file['file']) {
                $in_array = true;
                break;
            }
        }

        if (!$in_array) {
            throw new NotFoundHttpException('File not found.');
        }
    }

    /**
     * @param string $id Name File Dump
     * @return Response
     * @throws NotFoundHttpException
     * @throws YiiException
     */
    public function actionDownload($id): Response
    {
        static::testFileName($id);

        $dumpFile = BaseDump::getPath() . DIRECTORY_SEPARATOR . $id;

        return Yii::$app->response->sendFile($dumpFile);
    }

    /**
     * @param string $id Name File Dump
     * @return Response
     * @throws NotFoundHttpException
     * @throws YiiException
     */
    public function actionDelete($id): Response
    {
        static::testFileName($id);

        $dumpFile = BaseDump::getPath() . DIRECTORY_SEPARATOR . $id;

        if (unlink($dumpFile)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Dump deleted successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Error deleting dump.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * @return Response
     * @throws YiiException
     */
    public function actionDeleteAll(): Response
    {
        $fileList = BaseDump::getFilesList();

        if ($fileList) {
            $fail = [];
            $path = BaseDump::getPath() . DIRECTORY_SEPARATOR;
            foreach ($fileList as $file) {
                $fileName = $path . $file['file'];
                if (!unlink($fileName)) {
                    $fail[] = $file;
                }
            }

            if (empty($fail)) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'All dumps successfully removed.'));
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app', 'Error deleting dumps.'));
            }
        }

        return $this->redirect(['index']);
    }
}
