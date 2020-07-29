<?php

namespace app\themes;

use yii\base\Theme;

class BasicTheme extends Theme
{
    public function init(): void
    {
        parent::init();
        $this->basePath = '@app/themes/basic';
        $this->baseUrl = '@web/themes/basic';
    }
}
