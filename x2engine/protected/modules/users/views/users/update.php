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
	array('label'=>Yii::t('profile','Social Feed'),'url'=>array('/site/whatsNew')),
	array('label'=>Yii::t('users','Manage Users'), 'url'=>array('admin')),
	array('label'=>Yii::t('users','Create User'), 'url'=>array('create')),
	array('label'=>Yii::t('users','Invite Users'), 'url'=>array('inviteUsers')),
	array('label'=>Yii::t('users','View User'), 'url'=>array('view', 'id'=>$model->id)),
  	array('label'=>Yii::t('profile','View Profile'),'url'=>array('/profile/view','id'=>$model->id)),
	array('label'=>Yii::t('users','Update User')),
	array('label'=>Yii::t('users','Delete User'), 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>Yii::t('app','Are you sure you want to delete this item?'))),

));
?>

<?php $model->password=''; ?>
<div class="page-title icon users"><h2><span class="no-bold"><?php echo Yii::t('module','Update'); ?>:</span> <?php echo $model->firstName,' ',$model->lastName; ?></h2></div>


<?php echo $this->renderPartial('_form', array('model'=>$model, 'groups'=>$groups, 'roles'=>$roles,'selectedGroups'=>$selectedGroups,'selectedRoles'=>$selectedRoles,)); ?>