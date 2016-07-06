<?php
/***********************************************************************************
 * X2CRM is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2016 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. on our website at www.x2crm.com, or at our
 * email address: contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 **********************************************************************************/

Yii::app()->clientScript->registerScriptFile(
    Yii::app()->controller->assetsUrl.'/js/RecordViewController.js');

$supportsActionHistory = (bool) $this->asa ('MobileActionHistoryBehavior');

$authParams['X2Model'] = $model;

$this->onPageLoad ("
    x2.main.controllers['$this->pageId'] = new x2.RecordViewController (".CJSON::encode (array (
        'modelName' => get_class ($model),
        'modelId' => $model->id,
        'myProfileId' => Yii::app()->params->profile->id,
        'translations' => array (
            'deleteConfirm' => Yii::t('mobile', 'Are you sure you want to delete this record?'),
            'deleteConfirmOkay' => Yii::t('mobile', 'Okay'),
            'deleteConfirmCancel' => Yii::t('mobile', 'Cancel'),
        ),
        'supportsActionHistory' => $supportsActionHistory,
    )).");
", CClientScript::POS_END);


if ($model instanceof X2Model &&
    $this->hasMobileAction ('mobileDelete') && $this->hasMobileAction ('mobileUpdate') &&
    Yii::app()->user->checkAccess(ucfirst ($this->module->name).'Delete', $authParams)) {
?>

<div data-role='popup' id='settings-menu'>
    <ul data-role='listview' data-inset='true'>
        <li>
            <a class='delete-button requires-confirmation' 
             href='<?php echo $this->createAbsoluteUrl ('mobileDelete', array (
                'id' => $model->id,
             )); ?>'><?php 
                echo CHtml::encode (Yii::t('mobile', 'Delete')); ?></a>
            <div class='confirmation-text' style='display: none;'>
                <?php
                echo CHtml::encode (
                    Yii::t('app', 'Are you sure you want to delete this {type}?', array (
                        '{type}' => lcfirst ($model->getDisplayName (false)),
                    )));
                ?>
            </div>
        </li>
    </ul>
</div>

<?php
}
?>

<div class='refresh-content' data-refresh-selector='.page-title'>
    <h1 class='page-title ui-title'>
    <?php
    if ($model instanceof Profile) {
        echo CHtml::encode (Modules::displayName (false, 'Users'));
    } else {
        echo CHtml::encode ($this->getModuleObj ()->getDisplayName (false));
    }
    ?>
    </h1>
</div>
<?php

if ($model instanceof X2Model) {
    if ($this->hasMobileAction ('mobileUpdate') &&
        Yii::app()->user->checkAccess(ucfirst ($this->module->name).'Update', $authParams)) {
    ?>

    <div class='refresh-content' data-refresh-selector='.header-content-right'>
        <div class='header-content-right'>
            <div class='edit-button ui-btn icon-btn' 
             data-x2-url='<?php echo $this->createAbsoluteUrl ('mobileUpdate', array (
                'id' => $model->id
             )); ?>'>
            <?php
            echo X2Html::fa ('pencil');
            ?>
            </div>
        </div>
    </div>

    <?php
    }
}

if ($supportsActionHistory) {
?>
<div class='record-view-tabs'>
    <div data-role='navbar' class='record-view-tabs-nav-bar'>
        <ul>
            <li class='record-view-tab' data-x2-tab-name='record-details'>
                <a href='<?php echo '#'.MobileHtml::namespaceId ('detail-view-outer'); ?>'><?php 
                echo CHtml::encode (Yii::t('mobile', 'Details'));
                ?>
                </a>
            </li>
            <li class='record-view-tab' data-x2-tab-name='action-history'>
                <a href='<?php echo '#'.MobileHtml::namespaceId ('action-history-chart'); ?>'><?php 
                //echo CHtml::encode (Yii::t('mobile', 'History'));
                echo CHtml::encode (Yii::t('mobile', 'Action History'));
                ?>
                </a>
            </li>
            <li class='record-view-tab' data-x2-tab-name='action-history'>
                <a href='<?php echo '#'.MobileHtml::namespaceId ('action-history'); ?>'><?php 
                //echo CHtml::encode (Yii::t('mobile', 'History'));
                echo CHtml::encode (Yii::t('mobile', 'Attachments'));
                ?>
                </a>
            </li>
        </ul>
    </div>

    <div id='<?php echo MobileHtml::namespaceId ('detail-view-outer');?>'>
    <?php
}
    
    $this->renderPartial ('application.modules.mobile.views.mobile._recordView', array (
        'model' => $model
    ));

    if ($supportsActionHistory) {
    ?>
    </div>
    <div id='<?php echo MobileHtml::namespaceId ('action-history');?>' class='action-history-outer'>

    <?php
        $this->renderPartial ('application.modules.mobile.views.mobile._actionHistory', array (
            'model' => $model
        ));
    ?>
    </div>
    <div id='<?php echo MobileHtml::namespaceId ('action-history-chart');?>' class='action-history-outer'>

    <?php
        $this->renderPartial ('application.modules.mobile.views.mobile._actionHistoryList', array (
            'model' => $model,
        ));
    ?>
    </div>
</div>
<?php
    }
?>
