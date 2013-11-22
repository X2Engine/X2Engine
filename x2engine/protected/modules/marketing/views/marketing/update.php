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

$this->pageTitle = $model->name;

$authParams['assignedTo'] = $model->createdBy;
$this->actionMenu = $this->formatMenu(array(
	array('label'=>Yii::t('marketing','All Campaigns'), 'url'=>array('index')),
	array('label'=>Yii::t('module','Create'), 'url'=>array('create')),
	array('label'=>Yii::t('module','View'), 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>Yii::t('module','Update')),
	array(
        'label'=>Yii::t('module','Delete'), 'url'=>'#',
        'linkOptions'=>array(
            'submit'=>array('delete','id'=>$model->id),
            'confirm'=>Yii::t('app','Are you sure you want to delete this item?')
        )
    ),
	array('label'=>Yii::t('contacts','Contact Lists'), 'url'=>array('/contacts/contacts/lists')),
	array(
        'label'=>Yii::t('marketing','Newsletters'), 
        'url'=>array('/marketing/weblist/index'),
        'visible'=>(Yii::app()->params->edition==='pro')
    ),
	array('label'=>Yii::t('marketing','Web Lead Form'), 'url'=>array('webleadForm')),
	array(
        'label'=>Yii::t('marketing','Web Tracker'), 
        'url'=>array('webTracker'),
        'visible'=>(Yii::app()->params->edition==='pro')
    ),
	array(
        'label'=>Yii::t('app','X2Flow'),
        'url'=>array('/studio/flowIndex'),
        'visible'=>(Yii::app()->params->edition==='pro')
    ),
),$authParams);

$form = $this->beginWidget('CActiveForm', array(
	'id'=>'campaign-form',
	'enableAjaxValidation'=>false
));
?>
<div class="page-title icon marketing">
	<h2><?php echo $model->name; ?></h2>
	<?php echo CHtml::submitButton(Yii::t('app','Save'),array('class'=>'x2-button highlight right')); ?>
</div>
<?php
$this->renderPartial('_form', array('model'=>$model, 'modelName'=>'Campaign','form'=>$form));

$this->endWidget();
