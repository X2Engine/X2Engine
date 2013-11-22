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

/**
 * Widget class for displaying all available inline actions.
 *
 * Displays tabs for "log a call","new action" and the like.
 *
 * @package X2CRM.components
 */
class Publisher extends X2Widget {

    public $associationType;        // type of record to associate actions with
    public $associationId = '';        // record to associate actions with
    public $assignedTo = null;    // user actions will be assigned to by default

    // show all tabs by default
    public $showLogACall = true;
    public $showNewAction = true;
    public $showNewComment = true;
    public $showNewEvent = false;
    public $halfWidth = false;
    public $showQuickNote = true;
    public $showLogTimeSpent = true;
    public $model;

    public $viewParams = array(
        'halfWidth',
        'model',
        'showLogACall',
        'showNewComment',
        'showNewEvent',
        'showNewAction',
        'showQuickNote',
        'showLogTimeSpent',
    );


    public function run() {
        $model = new Actions;
        $model->associationType = $this->associationType;
        $model->associationId = $this->associationId;
        if($this->assignedTo)
            $model->assignedTo = $this->assignedTo;
        else
            $model->assignedTo = Yii::app()->user->getName();

        Yii::app()->clientScript->registerScript('loadEmails', "
            /**
             * Ad-hoc quasi-validation for the publisher
             */
            x2.publisher.beforeSubmit = function() {
                if($('#Actions_actionDescription').val() == '') {
                    alert('".addslashes(Yii::t('actions', 'Please enter a description.'))."');
                    return false;
                } else {
                    // show saving... icon
                    \$('.publisher-text').animate({opacity: 0.0});
                    \$('#publisher-saving-icon').animate({opacity: 1.0});
                }
                return true; // form is sane: submit!
            }

            //
            x2.publisher.loadFrame = function (id,type){
                if(type!='Action' && type!='QuotePrint'){
                    var frame='<iframe style=\"width:99%;height:99%\" src=\"".(Yii::app()->controller->createUrl('/actions/actions/viewEmail'))."?id='+id+'\"></iframe>';
                }else if(type=='Action'){
                    var frame='<iframe style=\"width:99%;height:99%\" src=\"".(Yii::app()->controller->createUrl('/actions/actions/viewAction'))."?id='+id+'&publisher=true\"></iframe>';
                } else if(type=='QuotePrint'){
                    var frame='<iframe style=\"width:99%;height:99%\" src=\"".(Yii::app()->controller->createUrl('/quotes/quotes/print'))."?id='+id+'\"></iframe>';
                }
                if(typeof x2.actionFrames.viewEmailDialog != 'undefined') {
                    if($(x2.actionFrames.viewEmailDialog).is(':hidden')){
                        $(x2.actionFrames.viewEmailDialog).remove();

                    }else{
                        return;
                    }
                }

                x2.actionFrames.viewEmailDialog = $('<div></div>', {id: 'x2-view-email-dialog'});

                x2.actionFrames.viewEmailDialog.dialog({
                    title: '".Yii::t('app', 'View history item') /* Changed to generic title from "View" +type because there's no practical way to translate javascript variables */."',
                    autoOpen: false,
                    resizable: true,
                    width: '650px',
                    show: 'fade'
                });
                jQuery('body')
                    .bind('click', function(e) {
                        if(jQuery('#x2-view-email-dialog').dialog('isOpen')
                            && !jQuery(e.target).is('.ui-dialog, a')
                            && !jQuery(e.target).closest('.ui-dialog').length
                        ) {
                            jQuery('#x2-view-email-dialog').dialog('close');
                        }
                    });

                x2.actionFrames.viewEmailDialog.data('inactive', true);
                if(x2.actionFrames.viewEmailDialog.data('inactive')) {
                    x2.actionFrames.viewEmailDialog.append(frame);
                    x2.actionFrames.viewEmailDialog.dialog('open').height('400px');
                    x2.actionFrames.viewEmailDialog.data('inactive', false);
                } else {
                    x2.actionFrames.viewEmailDialog.dialog('open');
                }
            }
            
            $(document).on('ready',function(){
                var t;
                $(document).on('mouseenter','.email-frame',function(){
                    var id=$(this).attr('id');
                    t=setTimeout(function(){x2.publisher.loadFrame(id,'Email')},500);
                });
                $(document).on('mouseleave','.email-frame',function(){
                    clearTimeout(t);
                });
                $('.quote-frame').mouseenter(function(){
                    var id=$(this).attr('id');
                    t=setTimeout(function(){x2.publisher.loadFrame(id,'Quote')},500);
                }).mouseleave(function(){
                    clearTimeout(t);
                }); // Legacy quote pop-out view
        $('.quote-print-frame').mouseenter(function(){
            var id=$(this).attr('id');
            t=setTimeout(function(){x2.publisher.loadFrame(id,'QuotePrint')},500);
        }).mouseleave(function(){
            clearTimeout(t);
        }); // New quote pop-out view
            });
        ", CClientScript::POS_HEAD);
        Yii::app()->clientScript->registerCss('recordViewPublisherCss', '
            #log-time-spent-form #action-event-panel .row,
            #log-a-call-form #action-event-panel .row {
                max-width: 405px;
            }
            #action-duration {
                margin-top: 15px;
                margin-left: 43px;
            }
            #log-a-call-form .event-panel-second-cell,
            #log-time-spent-form .event-panel-second-cell {
                margin-left: 5px;
            }
            .history.half-width #log-a-call-form .cell:first-child,
            .history.half-width #log-time-spent-form .cell:first-child {
                margin-right: 0 !important;
            }
            .history.half-width #log-a-call-form .event-panel-second-cell,
            .history.half-width #log-time-spent-form .event-panel-second-cell {
                margin-left: 0px !important;
                float:right !important;
                margin-right: 0 !important;
                width: 150px;
            }
            .history.half-width #action-duration {
                margin-left: 0px !important;
            }
            #action-duration .action-duration-display {
                font-size: 30px;
                font-family: Consolas, monaco, monospace;
            }
            #action-duration span.action-duration-display {
                vertical-align: top;
            }
            #action-duration input {
                width: 50px;
            }
            #action-duration .action-duration-input {
                display:inline-block;
            }
            #action-duration label {
                font-size: 10px;
            }
        ');

        if($this->showNewEvent){
            Yii::app()->clientScript->registerCss('calendarSpecificWidgetStyle', "
        .publisher-widget-title {
            color: #222;
            font-weight: bold;
        }
        .publisher-first-row {
            margin-top: 8px;
        }
        #publisher-form .form {
            background: #eee;
        }
        #publisher-form textarea {
            min-width: 100%;
            max-width: 100%;
            width: 100%;
        }
    ");
        }

        if(!$this->halfWidth){
            // set date, time, and region format for when javascript replaces datetimepicker
            // datetimepicker is replaced in the calendar module when the user clicks on a day
            $dateformat = Formatter::formatDatePicker('medium');
            $timeformat = Formatter::formatTimePicker();
            $ampmformat = Formatter::formatAMPM();
            $region = Yii::app()->locale->getLanguageId(Yii::app()->locale->getId());
            if($region == 'en')
                $region = '';
        }

        // save default values of fields for when the publisher is submitted and then reset
        Yii::app()->clientScript->registerScript('defaultValues', "
$(function() {

    ".($this->halfWidth ? "
    // turn on jquery tabs for the publisher
    $('#tabs').tabs({
        activate: function(event, ui) { x2.publisher.tabSelected(event, ui); },
    });
    $(document).on('change','#quickNote2',function(){
        $('#Actions_actionDescription').val($(this).val());
    });
    ":"
    x2.publisher.isCalendar = " . ($this->showNewEvent ? 'true' : 'false') . ";

    if (!x2.publisher.isCalendar) {
        $('#tabs').tabs({
            select: function(event, ui) { x2.publisher.tabSelected(event, ui); },
        });
    }
    ")."


    if($('#tabs .ui-state-active').length !== 0) { // if publisher is present (prevents a javascript error if publisher is not present)
        var selected = $('#tabs .ui-state-active').attr('aria-controls');
        x2.publisher.switchToTab(selected);
    }

    $('#publisher-form select, #publisher-form input[type=text], #publisher-form textarea').each(function(i) {
        $(this).data('defaultValue', $(this).val());
    });

    $('#publisher-form input[type=checkbox]').each(function(i) {
        $(this).data('defaultValue', $(this).is(':checked'));
    });

    // highlight save button when something is edited in the publisher
    $('#publisher-form input, #publisher-form select, #publisher-form textarea').focus(function(){
        $('#save-publisher').addClass('highlight');
        ".($this->halfWidth ? "
        $('#publisher-form textarea').height(80);
        $(document).unbind('click.publisher').bind('click.publisher',function(e) {
            if(!$(e.target).parents().is('#publisher-form, .ui-datepicker')
                && $('#publisher-form textarea').val()=='') {
                $('#save-publisher').removeClass('highlight');
                $('#publisher-form textarea').animate({'height':22},300);
            }
        });"
        :"")."
    });

    ".($this->halfWidth?"":"
    // position the saving icon for the publisher (which starts invisible)
    var publisherLabelCenter = parseInt($('.publisher-label').css('width'), 10)/2;
    var halfIconWidth = parseInt($('#publisher-saving-icon').css('width'), 10)/2;
    var iconLeft = publisherLabelCenter - halfIconWidth;
    $('#publisher-saving-icon').css('left', iconLeft + 'px');

    // set date and time format for when datetimepicker is recreated
    $('#publisher-form').data('dateformat', '$dateformat');
    $('#publisher-form').data('timeformat', '$timeformat');
    $('#publisher-form').data('ampmformat', '$ampmformat');
    $('#publisher-form').data('region', '$region');
    ")."
});");

        $that = $this;
        $this->model = $model;
        $this->render('publisher',array_combine($this->viewParams,array_map(function($p)use($that){return $that->$p;},$this->viewParams)));
    }
}
