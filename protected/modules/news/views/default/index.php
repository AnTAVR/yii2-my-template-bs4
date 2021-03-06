<?php

/* @var $this View */
/* @var $data array */

/* @var $pagination Pagination */

use app\helpers\CSS;
use app\modules\news\models\News;
use yii\bootstrap4\Html;
use yii\data\Pagination;
use yii\web\View;
use yii\widgets\LinkPager;

$this->title = Yii::t('app', 'News');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="clearfix <?= CSS::generateCurrentClass() ?>">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= LinkPager::widget(['pagination' => $pagination,]) ?>

    <?php
    $col = 1;
    $i = 0;
    foreach ($data as $model) {
        /* @var $model News */
        if (!$i) {
            echo '<div class="row">', "\n";
            $open = true;
        }
        echo $this->render('_card', ['model' => $model, 'class' => 'col-md-6']);
        if ($i >= $col) {
            echo '</div>', "\n\n";
            $i = 0;
        } else {
            $i++;
        }
    }
    if ($i) {
        echo '</div>', "\n\n";
    }
    ?>

    <?= LinkPager::widget(['pagination' => $pagination,]) ?>

</div>