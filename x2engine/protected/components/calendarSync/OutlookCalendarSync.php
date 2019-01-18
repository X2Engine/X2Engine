<?php
/***********************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2 Engine, Inc. Copyright (C) 2011-2019 X2 Engine Inc.
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
 * You can contact X2Engine, Inc. P.O. Box 610121, Redwood City,
 * California 94061, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2 Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2 Engine".
 **********************************************************************************/



class OutlookCalendarSync extends CalDavSync {
    
    /**
     * The URL format for Outlook calendars
     * @var string 
     */
    public $calendarUrl = 'https://graph.microsoft.com/v1.0/me/calendars/{calendarId}/events';

    /**
     * Returns an oAuthToken generated by the OutlookAuthenticator class
     * @return array
     * @throws CException
     */
    protected function authenticate() {
        $client = new OutlookAuthenticator('calendar');
        $access = $client->getAccessToken();
        return array(
            'oAuthToken' => $access,
            'username' => null,
            'password' => null,
        );
    }
    
    /**
     * Retrieves up to date information about the status of a remote calendar,
     * including the ctag and syncToken which can be used to determine if any
     * changes have been made and generate a diff of those changes
     * @return array
     */
    protected function getSyncInfo() {
    }
    
    /**
     * Handles synchronization of X2 and CalDav calendars. If an X2Calendar does
     * not yet have a ctag (it has never before been synced) an outbount sync is
     * performed to transfer X2 calendar events into the CalDav server. If a
     * sync token has been provided, it will use that instead of attempting a
     * full sync.
     */
    public function sync() {
            if (isset($this->owner->syncToken)) {
                $this->syncWithToken();
            } else {
                $this->syncWithoutToken();
            }
            $this->owner->save();
    }
    
    /**
     * Performs a full synchronization in the event that there is no token to
     * calculate a diff from. This function is called after outboundSync to ensure
     * no Actions which were already in X2 are deleted
     */
    protected function syncWithoutToken() {
        $calendarEvents = $this->client->getOutlook($this->owner->remoteCalendarUrl, "");
        
        $paths = $this->createUpdateActions($calendarEvents, true);
        $pathList = AuxLib::bindArray($paths);
        $bindParams = array(':calId' => $this->owner->id);
        $deletedActionCmd = Yii::app()->db->createCommand()
                ->select('a.id')
                ->from('x2_actions a')
                ->join('x2_action_meta_data b', 'a.id = b.actionId');
        if(!empty($pathList)){
            $bindParams = array_merge(
                $bindParams, $pathList);
            $deletedActionCmd->where('a.calendarId = :calId AND b.remoteCalendarUrl NOT IN ' . AuxLib::arrToStrList(array_keys($pathList)), $bindParams);
        }else{
            $deletedActionCmd->where('a.calendarId = :calId', $bindParams);
        }
        $deletedActions = $deletedActionCmd->queryColumn();
        if(!empty($deletedActions)){
            $actionIdParams = AuxLib::bindArray($deletedActions);
            $reminderIds = Yii::app()->db->createCommand()
                    ->select('id')
                    ->from('x2_events')
                    ->where('associationType = "Actions" AND associationId IN '. AuxLib::arrToStrList($actionIdParams). ' AND type = "action_reminder"')
                    ->queryColumn();
            X2Model::model('Events')->deleteByPk($reminderIds);
            X2Model::model('Actions')->deleteByPk($deletedActions);
        }
    }
    
    /**
     * Creates or updates Actions in X2 from remote calendar event data
     * @param array $calendarData XML data of remote calendar events
     * @param boolean $return Whether or not to return the paths
     * @return array A list of paths of created/updated events
     */
    protected function createUpdateActions($calendarData, $return = false) {
        if ($return) {
            $paths = array();
        }
        
        $calendar = CJSON::decode($calendarData['body']);
        $calendars = $calendar['value'];
        
        foreach ($calendars as $event) {
            $eventEtag = $event['@odata.etag'];
            $eventVObj = $event;
            $actionMetaData = X2Model::model('ActionMetaData')->findByAttributes(array('remoteCalendarUrl' => $event['id']));
            if ($return) {
                $paths[] = $event['id'];
            }
            if (isset($actionMetaData)) {
                $action = X2Model::model('Actions')->findByPk($actionMetaData->actionId);
                    $this->updateAction($action, $eventVObj, array(
                        'etag' => $eventEtag,
                    ));
            } else {
                $this->createAction($eventVObj, array(
                    'etag' => $eventEtag,
                    'remoteCalendarUrl' => $event['id'],
                ));
            }
        }
        if ($return) {
            return $paths;
        }
    }
    
        /**
     * Updates an Action from a SabreDav VEvent
     */
    protected function updateAction($action, $calObject, $params = array()) {
        $action->etag = $params['etag'];
        $this->setActionAttributes($action, $calObject);
        $action->save();
    }
    
    /**
     * Converts a SabreDav VEvent object's attributes into X2 friendly attributes
     * and sets the provided Action's attributes to the processed data.
     * 
     * TODO: Handle recurring events
     */
    protected function setActionAttributes(&$action, $calObject) {
        $action->actionDescription = $calObject['subject'];
        if (!empty($calObject['bodyPreview'])) {
            if (!empty($calObject['subject'])) {
                $action->actionDescription .= "\n" . $calObject['bodyPreview'];
            } else {
                $action->actionDescription = $calObject['bodyPreview'];
            }
        }
        $action->visibility = 1;
        $action->assignedTo = 'Anyone';
        $action->calendarId = $this->owner->id;
        $action->associationType = 'calendar';
        $action->associationName = 'Calendar';
        $action->type = 'event';
        $action->remoteSource = 1;
        if ($calObject['isAllDay'] == true) { // All day event
            $action->dueDate = strtotime($calObject['start']['dateTime']);
            // Subtract 1 second to fix all day display issue in Calendar
            $action->completeDate = strtotime($calObject['end']['dateTime']) - 1;
            $action->allDay = 1;
        } else {
            $timezone = new \DateTimeZone('UTC');
            $startTime = new \DateTime($calObject['start']['dateTime'], $timezone);
            if(is_null($calObject['end']['dateTime'])){
                $endTime = $startTime;
            } else {
                $endTime = new \DateTime($calObject['end']['dateTime'], $timezone);
            }
            $action->dueDate = $startTime->getTimestamp();
            $action->completeDate = $endTime->getTimestamp();
        }
    }
    
        /**
     * Either create or update a remote calendar event associated with an Action
     */
    public function syncActionToCalendar($action) {
        if (empty($action->remoteCalendarUrl)) {
            $calObject = $this->createCalObject($action);
        } else {
            $calObject = $this->updateCalObject($action);
        }
    }
    
    /** CUSTOM FOR OUTLOOK
     * Creates a VEvent object from an Action and sends it to a remote CalDav server
     */
    protected function createCalObject($action) {
        $calObj = new Sabre\VObject\Component\VCalendar();
        $vevent = new Sabre\VObject\Component\VEvent('VEVENT');
             
        //$this->setEventAttributes($vevent, $action);
        $uniqueId = UUID::v4();
        $vevent->add('UID', $uniqueId);
        $calObj->add($vevent);
       
        //microsoft only accepts UTC
        $timezone = "UTC";
        
        $newEvent = $this->client->postOutlook($this->owner->remoteCalendarUrl, $action , $timezone);
        $newEventBody = $newEvent['body'];
        $newEventId = CJSON::decode($newEventBody);
        
        if ($newEvent != false ) {
            $newEventData = $this->client->getOutlook( "https://graph.microsoft.com/v1.0/me/events/" . $newEventId['id'], "");
            $metaData = ActionMetaData::model()->findByAttributes(array('actionId' => $action->id));
            if (!isset($metaData)) {
                $metaData = new ActionMetaData();
                $metaData->actionId = $action->id;
            }
            $newEventDataBody = CJSON::decode($newEventData['body']);
            $metaData->etag = $newEventDataBody['@odata.etag'];
            $metaData->remoteCalendarUrl = $uniqueId;
            $metaData->save();
        }
    }
    
    /**
     * Updates a VEvent object associated with an Action and sends it to a remote CalDav server
     */
    protected function updateCalObject($action) {
        
        $eventData = $this->client->getOutlook( "https://graph.microsoft.com/v1.0/me/events/" . $action->remoteCalendarUrl, "");
        $calObj = CJSON::decode($eventData['body']);
        $timezone = "UTC";
        $this->setEventAttributes($calObj, $action);
        $this->client->patchOutlook( "https://graph.microsoft.com/v1.0/me/events/" . $action->remoteCalendarUrl, $action, $timezone);
    }
    
    /**
     * Converts an Action's attributes to CalDav friendly attributes and modifies
     * the provided VEvent with them
     */
    protected function setEventAttributes(&$vevent, $action) {

        $startTime = new Sabre\VObject\Property\DateTime('DTSTART');
        $startDateTime = new \DateTime('@' . $action->dueDate);
        $startTime->setDateTime($startDateTime);
        $endTime = new Sabre\VObject\Property\DateTime('DTEND');
        if(empty($action->completeDate)){
            $action->completeDate = $action->dueDate;
        }
        $endDateTime = new \DateTime('@' . $action->completeDate);
        $endTime->setDateTime($endDateTime);
        $vevent['start']['dateTime'] = $startTime;
        $vevent['end']['dateTime'] = $endTime;
        $vevent['subject'] = $action->actionDescription;

        return $vevent;
    }
    
    /** CUSTOM FOR OUTLOOK
     * Attempt to delete a remote calendar event associated with a given Action
     */
    public function deleteAction($action) {
        try{
            if(isset($action->remoteCalendarUrl)){
                $this->client->deleteOutlook("https://graph.microsoft.com/v1.0/me/events/" . $action->remoteCalendarUrl, $action);
            }
        } catch (Exception $e){
            
        }
    }
    
}
