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
?>
<div class="page-title"><h2><?php echo Yii::t('admin','Edit Dropdown'); ?></h2></div>
<div class="form">
<?php
$list=Dropdowns::model()->findAll();
$names=array();
foreach($list as $dropdown){
    $names[$dropdown->name]=$dropdown->name;
}
?>
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'criteria-form',
	'enableAjaxValidation'=>false,
        'action'=>'editDropdown',
)); ?>

	<em><?php echo Yii::t('app','Fields with <span class="required">*</span> are required.'); ?></em><br />

        <div class="row">
            <?php echo $form->labelEx($model,'name'); ?>
            <?php echo $form->dropDownList($model,'name',$names,array(
                'empty'=>Yii::t('admin','Select a dropdown'),
                'ajax' => array(
                'type'=>'POST', //request type
                'url'=>CController::createUrl('/admin/getDropdown'), //url to call.
                //Style: CController::createUrl('currentController/methodToCall')
                'update'=>'#options', //selector to update
                //'data'=>'js:"modelType="+$("'.CHtml::activeId($model,'modelType').'").val()'
                //leave out the data key to pass all form values through
                ))); ?>
            <?php echo $form->error($model,'name'); ?>
        </div>

        <div id="workflow-stages">
            <label><?php echo Yii::t('admin','Dropdown Options');?></label>
            <ol id="options">

            </ol>
        </div>
        <a href="javascript:void(0)" onclick="addStage();" class="add-workflow-stage">[<?php echo Yii::t('admin','Add Option'); ?>]</a>

	<div class="row buttons">
        <br />
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('app','Save'):Yii::t('app','Save'),array('class'=>'x2-button')); ?>
	</div>
<?php $this->endWidget(); ?>
</div>
