<?php
/* @var $this yii\web\View */
$this->title = 'My Yii Application';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>Ваш профиль!</h1>

        <p class="lead">Добрый день, <?=Yii::$app->user->identity->username;?></p>

    </div>

    </div>
</div>
