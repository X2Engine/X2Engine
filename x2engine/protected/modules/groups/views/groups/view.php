<?php
/*********************************************************************************
 * Copyright (C) 2011-2013 X2Engine Inc. All Rights Reserved.
 *
 * X2Engine Inc.
 * P.O. Box 66752
 * Scotts Valley, California 95067 USA
 *
 * Company website: http://www.x2engine.com
 * Community and support website: http://www.x2community.com
 *
 * X2Engine Inc. grants you a perpetual, non-exclusive, non-transferable license
 * to install and use this Software for your internal business purposes.
 * You shall not modify, distribute, license or sublicense the Software.
 * Title, ownership, and all intellectual property rights in the Software belong
 * exclusively to X2Engine.
 *
 * THIS SOFTWARE IS PROVIDED "AS IS" AND WITHOUT WARRANTIES OF ANY KIND, EITHER
 * EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, AND NON-INFRINGEMENT.
 ********************************************************************************/

$this->actionMenu = $this->formatMenu(array(
	array('label'=>Yii::t('groups','Group List'), 'url'=>array('index')),
	array('label'=>Yii::t('groups','Create Group'), 'url'=>array('create')),
	array('label'=>Yii::t('groups','View')),
	array('label'=>Yii::t('groups','Update Group'), 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>Yii::t('groups','Delete Group'), 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>Yii::t('app','Are you sure you want to delete this item?'))),
));
?>
<div class="page-title icon groups">
	<h2><span class="no-bold"><?php echo Yii::t('groups','Group:'); ?></span> <?php echo $model->name; ?></h2>
</div>
<div class="form" style="min-height:300px;">
<?php $this->renderPartial('_view',array('data'=>$model,'users'=>$users)); ?>
</div>