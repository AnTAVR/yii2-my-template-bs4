<?php

use app\migrations\DefaultContent;
use app\modules\articles\models\Articles;
use app\modules\articles\traits\IActiveArticlesStatus;
use yii\db\Migration;

class m000001_002100_insert_articles extends Migration
{
    public const CONTENT_TITLE = 'article';
    public const COUNT = 3;

    public $tableName;

    public function init(): void
    {
        parent::init();
        $this->tableName = Articles::tableName();
    }

    public function up()
    {
        $content_short = str_replace('{image}', DefaultContent::CONTENT_IMAGE, DefaultContent::CONTENT_SHORT);
        $content_full = str_replace(['{short}', '{full}'], [
            str_replace('{image}', '', DefaultContent::CONTENT_SHORT),
            $content_short,
        ], DefaultContent::CONTENT_FULL);

        $styles = [
            ' float-left',
            ' float-right',
            ' float-none',
        ];
        for ($i = 0; $i++ < self::COUNT;) {
            $title = self::CONTENT_TITLE . $i;
            $style = $styles[array_rand($styles)];
            $this->insert($this->tableName, [
                'published_at' => time(),
                'status' => IActiveArticlesStatus::ACTIVE,

                'content_title' => $title,
                'content_short' => str_replace('{float}', $style, $content_short),
                'content_full' => str_replace(['{title}', '{float}'], [$title, $style], $content_full),

                'meta_url' => $title,
            ]);
        }
    }

    public function down()
    {
        for ($i = 0; $i++ < self::COUNT;) {
            $title = self::CONTENT_TITLE . $i;
            $this->delete($this->tableName, ['meta_url' => $title]);
        }
    }
}
