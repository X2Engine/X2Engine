<?php
/* * *******************************************************************************
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
 * ****************************************************************************** */

if(isset($_SESSION['filters'])){
    $filters=$_SESSION['filters'];
}else{
    $filters=array(
        'visibility'=>array(),
        'users'=>array(),
        'types'=>array(),
        'subtypes'=>array(),
    );
}
$visibility=array(
    '1'=>'Public',
    '0'=>'Private',
);
$socialSubtypes=json_decode(Dropdowns::model()->findByPk(113)->options,true);
$users=User::getNames();
$eventTypeList=Yii::app()->db->createCommand()
        ->select('type')
        ->from('x2_events')
        ->group('type')
        ->queryAll();
$eventTypes=array();
foreach($eventTypeList as $key=>$value){
    if($value['type']!='comment')
        $eventTypes[$value['type']]=Events::parseType($value['type']);
}
$profile=Yii::app()->params->profile;
$this->beginWidget('zii.widgets.CPortlet',
    array(
        'title'=>Yii::t('app', 'Filter Controls'),
        'id'=>'filter-controls',
    )
);
echo '<div class="x2-button-group">';
echo '<a href="#" id="simple-filters" class="x2-button'.
    ($profile->fullFeedControls?"":" disabled-link").'" style="width:42px">'.
    Yii::t('app','Simple').'</a>';
echo '<a href="#" id="full-filters" class="x2-button x2-last-child'.
    ($profile->fullFeedControls?" disabled-link":"").'" style="width:42px">'.
    Yii::t('app','Full').'</a>';
echo "</div>\n";
$this->endWidget();
$filterList=json_decode($profile->feedFilters,true);
echo "<div id='full-controls'".($profile->fullFeedControls?"":"style='display:none;'").
    ">";
$visFilters=$filters['visibility'];
$this->beginWidget('zii.widgets.CPortlet',
    array(
        'title'=>Yii::t('app', 'Visibility').
            CHtml::link(
                CHtml::image(
                    Yii::app()->theme->getBaseUrl()."/images/icons/".
                    ((!isset($filterList['visibility']) || 
                     $filterList['visibility'])?"Collapse":"Expand").
                    "_Widget.png"
                ),"#",
                array (
                    'title'=>'visibility',
                    'class'=>'activity-control-link',
                    'style'=>'float:right;padding-right:5px;'
                )
            ),
        'id'=>'visibility-filter',
        'htmlOptions'=>array(
            'class'=>
                ((!isset($filterList['visibility']) || $filterList['visibility'])?
                    "":"hidden-filter")
        )
    )
);
echo '<ul style="font-size: 0.8em; font-weight: bold; color: black;">';
foreach($visibility as $value=>$label) {
    echo "<li>\n";
    $checked = in_array($value,$visFilters)?false:true;
    $title = '';
    $class = 'visibility filter-checkbox';

    echo CHtml::checkBox($label, $checked,
        array(
            'title'=>$title,
            'class'=>$class,
        )
    );
    $filterDisplayName = $label; // capitalize filter name for label
    echo "<label for=\"$value\" title=\"$title\">".Yii::t('app',$label)."</label>";
    echo "</li>\n";
}
echo "</ul>\n";
$this->endWidget();
$userFilters=$filters['users'];
$this->beginWidget('zii.widgets.CPortlet',
    array(
        'title'=>Yii::t('app', 'Relevant Users').
            CHtml::link(
                CHtml::image(
                    Yii::app()->theme->getBaseUrl()."/images/icons/".
                    ((!isset($filterList['users']) || $filterList['users'])?
                        "Collapse":"Expand")."_Widget.png"),
                "#",
                array(
                    'title'=>'users',
                    'class'=>'activity-control-link',
                    'style'=>'float:right;padding-right:5px;'
                )
            ),
        'id'=>'user-filter',
        'htmlOptions'=>array(
            'class'=>
                ((!isset($filterList['users']) || $filterList['users'])?
                    "":"hidden-filter")
        )
    )
);
echo '<ul style="font-size: 0.8em; font-weight: bold; color: black;">';
foreach($users as $username=>$name) {
    echo "<li>\n";
    $checked = in_array($username,$userFilters)?false:true;
    $title = '';
    $class = 'users filter-checkbox';

    echo CHtml::checkBox($username, $checked,
        array(
            'title'=>$title,
            'class'=>$class,
        )
    );
    $filterDisplayName = $name; // capitalize filter name for label
    echo "<label for=\"$username\" title=\"$title\">".$name."</label>";
    echo "</li>\n";
}
echo "</ul>\n";
$this->endWidget();
$typeFilters=$filters['types'];
$this->beginWidget('zii.widgets.CPortlet',
    array(
        'title'=>Yii::t('app', 'Event Types').
            CHtml::link(
                CHtml::image(
                    Yii::app()->theme->getBaseUrl()."/images/icons/".
                    ((!isset($filterList['eventTypes']) || $filterList['eventTypes'])?
                        "Collapse":"Expand")."_Widget.png"
                ), "#",
                array(
                    'title'=>'eventTypes',
                    'class'=>'activity-control-link',
                    'style'=>'float:right;padding-right:5px;'
                )
            ),
        'id'=>'type-filter',
        'htmlOptions'=>array(
            'class'=>
                ((!isset($filterList['eventTypes']) || $filterList['eventTypes'])?
                    "":"hidden-filter")
        )
    )
);
echo '<ul style="font-size: 0.8em; font-weight: bold; color: black;">';
foreach($eventTypes as $type=>$name) {
    echo "<li>\n";
    $checked = in_array($type,$typeFilters)?false:true;
    $title = '';
    $class = 'event-type filter-checkbox';

    echo CHtml::checkBox($type, $checked,
        array(
            'title'=>$title,
            'class'=>$class,
        )
    );
    $filterDisplayName = $name; // capitalize filter name for label
    echo "<label for=\"$type\" title=\"$title\">".$name."</label>";
    echo "</li>\n";
}
echo "</ul>\n";
$this->endWidget();
$subFilters=$filters['subtypes'];
$this->beginWidget('zii.widgets.CPortlet',
    array(
        'title'=>Yii::t('app', 'Social Subtypes').
            CHtml::link(
                CHtml::image(
                    Yii::app()->theme->getBaseUrl()."/images/icons/".
                    ((!isset($filterList['subtypes']) || $filterList['subtypes'])?
                        "Collapse":"Expand")."_Widget.png"
                ),"#",
                array(
                    'title'=>'subtypes',
                    'class'=>'activity-control-link',
                    'style'=>'float:right;padding-right:5px;'
                )
            ),
        'id'=>'user-filter',
        'htmlOptions'=>array(
            'class'=>((!isset($filterList['subtypes']) || $filterList['subtypes']) ? 
                "":"hidden-filter")
        )
    )
);
echo '<ul style="font-size: 0.8em; font-weight: bold; color: black;">';
foreach($socialSubtypes as $key=>$value) {
    echo "<li>\n";
    $checked = in_array($key,$subFilters)?false:true;
    $title = '';
    $class = 'subtypes filter-checkbox';

        echo CHtml::checkBox($key, $checked,
            array(
                'title'=>$title,
                'class'=>$class,
            )
        );
        $filterDisplayName = $value; // capitalize filter name for label
        echo "<label for=\"$key\" title=\"$title\">".Yii::t('app',$value)."</label>";
        echo "</li>\n";
    }
    echo "</ul>\n";
    $this->endWidget();

    $this->beginWidget('zii.widgets.CPortlet',
        array(
            'title'=>Yii::t('app', 'Options').
                CHtml::link(
                    CHtml::image(
                        Yii::app()->theme->getBaseUrl()."/images/icons/".
                        ((!isset($filterList['options']) || $filterList['options'])?
                            "Collapse":"Expand")."_Widget.png"
                    ),"#",
                    array(
                        'title'=>'options',
                        'class'=>'activity-control-link',
                        'style'=>'float:right;padding-right:5px;'
                    )
                ),
            'id'=>'user-filter',
            'htmlOptions'=>array(
                'class'=>((!isset($filterList['options']) || $filterList['options'])?
                    "":"hidden-filter")
            )
        )
    );
    echo '<ul style="font-size: 0.8em; font-weight: bold; color: black;">';
    foreach(array('setDefault'=>"Set Default") as $key=>$value) {
        echo "<li>\n";
        $checked = false;
        $title = '';
        $class = 'default-filter-checkbox';

    echo CHtml::checkBox($key, $checked,
        array(
            'title'=>$title,
            'class'=>$class,
            'id'=>'filter-default'
        )
    );
    $filterDisplayName = $value; // capitalize filter name for label
    echo "<label for=\"$key\" title=\"$title\">".Yii::t('app',$value)."</label>";
    echo "</li>\n";
}
echo "</ul>\n";
echo "<br />";

echo "<div id='full-controls-button-container'>";
echo CHtml::link(
    Yii::t('app','Uncheck Filters'),'#',
    array('id'=>'toggle-filters-link','class'=>'x2-button'));
echo CHtml::link(
    Yii::t('app','Apply Filters'),'#',
    array('class'=>'x2-button','id'=>'apply-feed-filters'));
echo "</div>";
$this->endWidget();
echo "</div>";

echo "<div id='simple-controls'".
    ($profile->fullFeedControls?"style='display:none;'":"").">";

$this->beginWidget('zii.widgets.CPortlet',
    array(
        'title'=>Yii::t('app', 'Event Types'),
        'id'=>'type-filter',
    )
);
echo CHtml::link(
    Yii::t('app','All'),'#',
    array(
        'class'=>'x2-button filter-control-button',
        'id'=>'all-button',
        'style'=>'width:107px;'
    )
)."<br>";
foreach($eventTypes as $type=>$name) {
    echo CHtml::link(
        $name,'#',
        array(
            'class'=>'x2-button filter-control-button',
            'id'=>$type.'-button','style'=>'width:107px;'
        )
    )."<br>";
}
$this->endWidget();
echo "</div>";
Yii::app()->clientScript->registerScript('feed-filters','
    $("#apply-feed-filters").click(function(e){
        e.preventDefault();
        var visibility=new Array();
        $.each($(".visibility.filter-checkbox"),function(){
            if(typeof $(this).attr("checked")=="undefined"){
                visibility.push($(this).attr("name"));
            }
        });

        var users=new Array();
        $.each($(".users.filter-checkbox"),function(){
            if(typeof $(this).attr("checked")=="undefined"){
                users.push($(this).attr("name"));
            }
        });

        var eventTypes=new Array();
        $.each($(".event-type.filter-checkbox"),function(){
            if(typeof $(this).attr("checked")=="undefined"){
                eventTypes.push($(this).attr("name"));
            }
        });

        var subtypes=new Array();
        $.each($(".subtypes.filter-checkbox"),function(){
            if(typeof $(this).attr("checked")=="undefined"){
                subtypes.push($(this).attr("name"));
            }
        });

        var defaultCheckbox=$("#filter-default");
        var defaultFilters=false;
        if($(defaultCheckbox).attr("checked")=="checked"){
            defaultFilters=true;
        }
        var str=window.location+"";
        pieces=str.split("?");
        var str2=pieces[0];
        pieces2=str2.split("#");
        window.location= pieces2[0] + "?filters=true&visibility=" + visibility + 
            "&users=" + users+"&types=" + eventTypes +"&subtypes=" + subtypes + 
            "&default=" + defaultFilters;
    });
    $("#full-filters").click(function(e){
        e.preventDefault();
        $("#simple-controls").hide();
        $("#full-controls").show();
        $.ajax({
            url:"toggleFeedControls"
        });
        $(this).addClass("disabled-link");
        $(this).prev().removeClass("disabled-link");
    });
    $("#simple-filters").click(function(e){
        e.preventDefault();
        $("#full-controls").hide();
        $("#simple-controls").show();
        $.ajax({
            url:"toggleFeedControls"
        });
        $(this).addClass("disabled-link");
        $(this).next().removeClass("disabled-link");
    });
    $(".filter-control-button").click(function(e){
        e.preventDefault();
        var link=this;
        var visibility=new Array();
        var users=new Array();
        var eventTypes=new Array();
        var subtypes=new Array();
        var defaultFilters=new Array();
        var linkId=$(link).attr("id");
        if(linkId!="all-button"){
            $.each($(".filter-control-button"),function(){
                var id=$(this).attr("id");
                if(id!=$(link).attr("id")){
                    pieces=id.split("-");
                    item=pieces[0];
                    eventTypes.push(item);
                }
            });
        }
        var str=window.location+"";
        pieces=str.split("?");
        var str2=pieces[0];
        pieces2=str2.split("#");
        window.location = pieces2[0] + "?filters=true&visibility=" + visibility + 
            "&users=" + users + "&types=" + eventTypes + "&subtypes=" + subtypes + 
            "&default=" + defaultFilters;
    });
    $.each($(".hidden-filter"),function(){
        $(this).find(".portlet-content").hide();
    });
    $(".activity-control-link").click(function(e){
        e.preventDefault();
        var link=this;
        $.ajax({
            url:"toggleFeedFilters",
            data:{filter:$(this).attr("title")},
            success:function(data){
                if(data==1){
                    $(link).html(
                        "<img src=\'"+yii.themeBaseUrl+"/images/icons/Collapse_Widget'.
                            '.png\' />");
                    $(link).parents(".portlet-decoration").next().slideDown();
                }else if(data==0){
                    $(link).html("<img src=\'"+yii.themeBaseUrl+"/images/icons/'.
                        'Expand_Widget.png\' />");
                    $(link).parents(".portlet-decoration").next().slideUp();
                }
            }
        });
    });
');
