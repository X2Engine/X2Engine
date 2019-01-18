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

//Yii::app()->clientScript->registerScript('eventModal',);

?>

<div class="form publisher">
<?php
if ($showNewEvent) {
    echo '<span class="publisher-widget-title">' . Yii::t('app', 'New Event Publisher') . '</span>';
}
?>
    <div class="row publisher-first-row">
        <b><?php echo $form->labelEx($model, 'actionDescription'); ?></b>
        <div class="text-area-wrapper">
            <?php echo $form->textArea($model, 'actionDescription', array('rows' => 3, 'cols' => 40)); ?>
        </div>
    </div>
<?php echo CHtml::hiddenField('SelectedTab', $showNewEvent?'new-event':''); // currently selected tab ?>
    <?php echo $form->hiddenField($model, 'associationType'); ?>
    <?php echo $form->hiddenField($model, 'associationId'); ?>

    <div id="action-event-panel">
        <div class="row">
            <div class="cell">
                <?php echo $form->label($model, 'dueDate', array('id' => 'due-date-label')); ?>

                <?php // label for New Event ?>
                <?php echo CHtml::label(Yii::t('actions', 'Start Date'), 'Actions_dueDate', array('id' => 'start-date-label', 'style' => 'display: none;')); ?>

                <?php
                Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
                $this->widget('CJuiDateTimePicker', array(
                    'model' => $model, //Model object
                    'attribute' => 'dueDate', //attribute name
                    'mode' => 'datetime', //use "time","date" or "datetime" (default)
                    'options' => array(
                        'dateFormat' => Formatter::formatDatePicker('medium'),
                        'timeFormat' => Formatter::formatTimePicker(),
                        'ampm' => Formatter::formatAMPM(),
                        'changeMonth' => true,
                        'changeYear' => true
                    ), // jquery plugin options
                    'language' => (Yii::app()->language == 'en') ? '' : Yii::app()->getLanguage(),
                    'htmlOptions' => array('onClick' => "$('#ui-datepicker-div').css('z-index', '20');"), // fix datepicker so it's always on top
                ));
                ?>
            </div>
            <div class="cell">
                <?php echo $form->label($model, 'priority'); ?>
                <?php
                echo $form->dropDownList($model, 'priority', array(
                    '1' => Yii::t('actions', 'Low'),
                    '2' => Yii::t('actions', 'Medium'),
                    '3' => Yii::t('actions', 'High')));
                ?>
            </div>
            <div class="cell">
                <?php echo $form->label($model, 'assignedTo'); ?>
                <?php echo $form->dropDownList($model, 'assignedTo', X2Model::getAssignmentOptions(true, true), array('id' => 'actionsAssignedToDropdown')); ?>
            </div>

            <div class="cell">
                <?php echo $form->label($model, 'visibility'); ?>
                <?php $model->visibility = 1; // default visibility = public ?>
                <?php echo $form->dropDownList($model, 'visibility', array(0 => Yii::t('actions', 'Private'), 1 => Yii::t('actions', 'Public'), 2 => Yii::t('actions', "User's Group"))); ?>
            </div>

            <div class="cell">
                <?php echo $form->label($model, 'reminder'); ?>
                <?php echo $form->dropDownList($model, 'reminder', array('No' => Yii::t('actions', 'No'), 'Yes' => Yii::t('actions', 'Yes'))); ?>
            </div>
        </div>
        <div class="row">
            <div class="cell">
                <?php
                echo CHtml::label(Yii::t('actions', 'End Date'), 'Actions_completeDate', array('id' => 'end-date-label', 'style' => 'display: none;'));

                $model->dueDate = Formatter::formatDateTime(time());
                Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
                $this->widget('CJuiDateTimePicker', array(
                    'model' => $model, //Model object
                    'attribute' => 'completeDate', //attribute name
                    'mode' => 'datetime', //use "time","date" or "datetime" (default)
                    'options' => array(
                        'dateFormat' => Formatter::formatDatePicker('medium'),
                        'timeFormat' => Formatter::formatTimePicker(),
                        'ampm' => Formatter::formatAMPM(),
                        'changeMonth' => true,
                        'changeYear' => true,
                    ), // jquery plugin options
                    'language' => (Yii::app()->language == 'en') ? '' : Yii::app()->getLanguage(),
                    'htmlOptions' => array(
                        'onClick' => "$('#ui-datepicker-div').css('z-index', '20');", // fix datepicker so it's always on top
                        'style' => 'display: none;',
                        'id' => 'end-date-input',
                    ),
                ));
                ?>
            </div>
            <div class="cell">
                <?php echo $form->label($model, 'color'); ?>
                <?php echo $form->dropDownList($model, 'color', Actions::getColors()); ?>
            </div>
            <div class="cell">
                <?php echo $form->label($model, 'associationType'); ?>
                <?php
                echo $form->dropDownList($model, 'associationType', array_merge(array('none' => Yii::t('app','None')), Admin::getModelList()), array(
                    'ajax' => array(
                        'type' => 'POST', //request type
                        'url' => Yii::app()->controller->createUrl('/actions/actions/parseType'), //url to call.
                        //Style: CController::createUrl('currentController/methodToCall')
                        'update' => '#', //selector to update
                        'success' => 'function(data){
                                    if(data){
                                        $("#auto_select").autocomplete("option","source",data);
                                        $("#auto_select").val("");
                                        $("#auto_complete").show();
                                    }else{
                                        $("#auto_complete").hide();
                                    }
                                }'
                    )
                        )
                );
                echo $form->error($model, 'associationType');
                if ($model->associationType != 'none') {
                    $linkModel = X2Model::getModelName($model->associationType);
                } else {
                    $linkModel = null;
                }
                if (class_exists($linkModel) && X2Model::model($linkModel)->asa('X2LinkableBehavior')!=null) {
                    // Ensure the model has X2LinkableBehavior before trying to access one of its properties.
                    // This is because (to our chagrin) there are some exceptions, where the behaviors method is
                    // overridden and the X2Model children in question don't have the behavior-inherited property
                    // autoCompleteSource.
                    $linkSource = Yii::app()->controller->createUrl(X2Model::model($linkModel)->autoCompleteSource);
                } else {
                    $linkSource = "";
                }
                ?>
            </div>
            <div class="cell" id="auto_complete" style="display:none;">
                <?php
                echo $form->label($model, 'associationName');
                $this->widget('zii.widgets.jui.CJuiAutoComplete', array(
                    'name' => 'auto_select',
                    'value' => $model->associationName,
                    'source' => $linkSource,
                    'options' => array(
                        'minLength' => '2',
                        'select' => 'js:function( event, ui ) {
                        $("#' . CHtml::activeId($model, 'associationId') . '").val(ui.item.id);
                        $(this).val(ui.item.value);
                        return false;
                    }',
                    ),
                ));
                ?>
            </div>
        </div>
        <div class="row">
            <div class="cell">
                <?php echo $form->label($model, 'allDay'); ?>
                <?php echo $form->checkBox($model, 'allDay'); ?>
            </div>
        </div>

    </div>
</div>
<div id="log-a-call"></div>
<div id="new-action"></div>
<div id="new-comment"></div>
<div id="new-event"></div>
<?php
echo CHtml::ajaxSubmitButton(Yii::t('app', 'Save'), array('/actions/actions/publisherCreate'), array(
    'beforeSend' => "x2.publisher.beforeSubmit",
    'success' => "function() {
                        x2.publisher.updates();
                        x2.publisher.reset();
                        //$(document).trigger ('newlyPublishedAction');
                        \$('.publisher-text').animate({opacity: 1.0});
                        \$('#publisher-saving-icon').animate({opacity: 0.0});
                    }",
    'type' => 'POST',
        ), array('id' => 'save-publisher', 'class' => 'x2-button'));
?>