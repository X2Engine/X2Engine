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

Yii::import('application.models.X2Model');

/**
 * This is the model class for table "x2_quotes".
 *
 * @property array $adjustmentLines (read-only) Line items that are adjustments to the subtotal
 * @property Contacts $contact First contact associated with this quote.
 * @property array $lineItems All line items for the quote.
 * @property array $productLines (read-only) Line items that are products/services.
 * @package X2CRM.modules.quotes.models
 * @author David Visbal, Demitri Morgan <demitri@x2engine.com>
 */
class Quote extends X2Model {

	/**
	 * Holds the set of line items
	 * @var array
	 */
	private $_lineItems;

	private $_contact;

	/**
	 * Holds the set of line items to be deleted
	 * @var array
	 */
	private $_deleteLineItems;

	/**
	 * Value stored for {@link productLines}
	 * @var array
	 */
	private $_productLines;
	/**
	 * Value stored for {@link adjustmentLines}
	 * @var array
	 */
	private $_adjustmentLines;


	/**
	 * Whether the line item set has errors in it.
	 * @var bool
	 */
	public $hasLineItemErrors = false;
	public $lineItemErrors = array();

	public static function lineItemOrder($i0,$i1) {
		return $i0->lineNumber < $i1->lineNumber ? -1 : 1;
	}

	/**
	 * Magic getter for {@link lineItems}.
	 */
	public function getLineItems() {
		if (!isset($this->_lineItems)) {
			$lineItems = $this->getRelated('products');
			if(count(array_filter($lineItems,function($li){return empty($li->lineNumber);})) > 0) {
				// Cannot abide null line numbers. Use indexes to set initial line numbers!
				foreach($lineItems as $i => $li) {
					$li->lineNumber = $i;
					$li->save();
				}
			}
			usort($lineItems,'self::lineItemOrder');
			$this->_lineItems = array();
			foreach($lineItems as $li) {
				$this->_lineItems[(int) $li->lineNumber] = $li;
			}
		}
		return $this->_lineItems;
	}

	/**
	 * Magic getter for {@link adjustmentLines}
	 */
	public function getAdjustmentLines(){
		if(!isset($this->_adjustmentLines))
			$this->_adjustmentLines = array_filter($this->lineItems,function($li){return $li->isTotalAdjustment;});
		return $this->_adjustmentLines;
	}

	/**
	 * Magic getter for {@link contact}
	 *
	 * In earlier versions, there was a function that enabled associating more than
	 * one contact with a quote (that didn't work) by storing contact IDs in a
	 * space delineated list, {@link associatedContacts}. In case there are any
	 * records that reflect this, this method fetches the first; the way it
	 * retrieves the contact is meant to be backwards-compatible.
	 */
	public function getContact(){
		if(!isset($this->_contact)){
			$this->_contact = null;
			$contactIds = explode(' ', $this->associatedContacts);
			$contact = null;
			if(!empty($contactIds[0]))
				$this->_contact = Contacts::model()->findByPk($contactIds[0]);
		}
		return $this->_contact;
	}

	/**
	 * Magic getter for {@link productLines}
	 */
	public function getProductLines(){
		if(!isset($this->_productLines))
			$this->_productLines = array_filter($this->lineItems,function($li){return !$li->isTotalAdjustment;});
		return $this->_productLines;
	}

	/**
	 * Returns the static model of the specified AR class.
	 * @return Quotes the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array_merge(parent::relations(), array(
					'products' => array(self::HAS_MANY, 'QuoteProduct', 'quoteId', 'order' => 'lineNumber ASC'),
				));
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'x2_quotes';
	}

	public function behaviors() {
		return array_merge(parent::behaviors(), array(
					'X2LinkableBehavior' => array(
						'class' => 'X2LinkableBehavior',
						'module' => 'quotes'
					),
					'ERememberFiltersBehavior' => array(
						'class' => 'application.components.ERememberFiltersBehavior',
						'defaults' => array(),
						'defaultStickOnClear' => false
					)
				));
	}

	/**
	 * Check a new set of line items against the existing set and update/delete as necessary
	 *
	 * Note: line numbers should be computed client-side and thus shouldn't need to be recalculated.
	 *
	 * @param array $items Each entry is an associative array of QuoteProduct [attribute]=>[value] pairs
	 * @param integer $quoteId ID of quote for which to update items
	 * @param bool $save Whether or not to save changes in the database after finishing
	 * @return array Array of QuoteProduct instances representing the item set after changes.
	 * @throws CException
	 */
	public function setLineItems(array $items, $save = false) {
		$this->_deleteLineItems = array();
		if (count($items) === 0) {
			QuoteProduct::model()->deleteAllByAttributes(array('quoteId' => $this->id));
			return true;
		}

		// Check for valid input:
		$typeErrMsg = 'The setter of Quote.lineItems requires an array of QuoteProduct objects or [attribute]=>[value] arrays.';
		$firstElt = reset($items);
		$type = gettype($firstElt);
		if ($type != 'object' && $type != 'array') // Must be one or the other
			throw new Exception($typeErrMsg);
		if ($type == 'object') // If object, must be of the QuoteProduct class
			if (get_class($firstElt) != 'QuoteProduct')
				throw new Exception($typeErrMsg);

		// Gather existing line items into an array indexed by ID.
		$existingItemIds = array();
		$newItems = array();
		$itemSet = array();
		$existingItems = array();
		foreach ($this->lineItems as $item) {
			$existingItems[$item->id] = $item;
			$existingItemIds[] = (int) $item->id;
		}

		// Gather the new set of line items into arrays
		if (isset($items['']))
			unset($items['']);
		if ($type == 'object') {
			foreach ($items as $item) {
				if (in_array($item->id, $existingItemIds)) {
					$itemSet[$item->id] = $existingItems[$item->id];
					$itemSet[$item->id]->attributes = $item->attributes;
				} else {
					$newItems[] = $item;
				}
			}
		} else if ($type == 'array') {
			foreach ($items as $item) {
				$new = false;
				if (isset($item['id'])) {
					$id = $item['id'];
					if (in_array($id, $existingItemIds)) {
						$itemSet[$id] = $existingItems[$item['id']];
						$itemSet[$id]->attributes = $item;
					} else
						$new = true;
				} else
					$new = true;

				if ($new) {
					$itemObj = new QuoteProduct;
					$itemObj->attributes = $item;
					$newItems[] = $itemObj;
				}
			}
		}

		// Compute set changes:
		$itemIds = array_keys($itemSet);
		$deleteItemIds = array_diff($existingItemIds, $itemIds);
		$updateItemIds = array_intersect($existingItemIds, $itemIds);

		// Put all the items together into the same arrays
		$this->_lineItems = array_merge($newItems, array_values($itemSet));
		usort($this->_lineItems,'self::lineItemOrder');
		$this->_deleteLineItems = array_map(function($id) use($existingItems) {return $existingItems[$id];}, $deleteItemIds);

		// Remove symbols from numerical input values and convert to numeric.
		// Behavior:
		// - Use the quote's currency if it isn't empty.
		// - Use the app's currency otherwise.
		$defaultCurrency = empty($this->currency)?Yii::app()->params->admin->currency:$this->currency;
		$curSym = Yii::app()->locale->getCurrencySymbol($defaultCurrency);
		foreach($this->_lineItems as $lineItem) {
			$lineItem->quoteId = $this->id;
			if(empty($lineItem->currency))
				$lineItem->currency = $defaultCurrency;
			if($lineItem->isPercentAdjustment) {
				$lineItem->adjustment = Fields::strToNumeric($lineItem->adjustment,'percentage');
			} else {
				$lineItem->adjustment = Fields::strToNumeric($lineItem->adjustment,'currency',$curSym);
			}
			$lineItem->price = Fields::strToNumeric($lineItem->price,'currency',$curSym);
			$lineItem->total = Fields::strToNumeric($lineItem->total,'currency',$curSym);
		}

		// Validate
		$this->hasLineItemErrors = false;
		$this->lineItemErrors = array();
		foreach ($this->_lineItems as $item) {
			$itemValid = $item->validate();
			if (!$itemValid) {
				$this->hasLineItemErrors = true;
				foreach ($item->errors as $attribute => $errors)
					foreach ($errors as $error)
						$this->lineItemErrors[] = $error;
			}
		}
		$this->lineItemErrors = array_unique($this->lineItemErrors);

		// Reset derived properties:
		$this->_adjustmentLines = null;
		$this->_productLines = null;

		// Save
		if($save && !$this->hasLineItemErrors)
			$this->saveLineItems();
	}

	/**
	 * Saves line item set changes to the database.
	 */
	public function saveLineItems(){
		// Insert/update new/existing items:
		if(isset($this->_lineItems)){
			foreach($this->_lineItems as $item){
				$item->quoteId = $this->id;
				$item->save();
			}
		}
		if(isset($this->_deleteLineItems)) {
			// Delete all deleted items:
			foreach($this->_deleteLineItems as $item)
				$item->delete();
			$this->_deleteLineItems = null;
		}
	}

	/**
	 * Creates an action history event record in the contact/account
	 */
	public function createActionRecord() {
		$now = time();
		$actionAttributes = array(
			'type' => 'quotes',
			'actionDescription' => $this->id,
			'completeDate' => $now,
			'dueDate' => $now,
			'createDate' => $now,
			'lastUpdated' => $now,
			'complete' => 'Yes',
			'completedBy' => $this->createdBy,
			'updatedBy' => $this->updatedBy
		);
		$ids = explode(',',$this->associatedContacts);
		if(!empty($ids)) {
			$cid = trim($ids[0]);
			$action = new Actions();
			$action->attributes = $actionAttributes;
			$action->associationType = 'contacts';
			$action->associationId = $cid;
			$action->save();
		}
		if(!empty($this->accountName)) {
			$action = new Actions();
			$action->attributes = $actionAttributes;
			$action->associationType = 'accounts';
			$action->associationId = $this->accountName;
			$action->save();
		}
	}

	/**
	 * Creates an event record for the creation of the model.
	 */
	public function createEventRecord() {
//		$event = new Events();
//		$event->type = 'record_create';
//		$event->subtype = 'quote';
//		$event->associationId = $this->id;
//		$event->associationType = 'Quote';
//		$event->timestamp = time();
//		$event->lastUpdated = $event->timestamp;
//		$event->user = $this->createdBy;
//		$event->save();
	}

	public static function getStatusList() {
		$field = Fields::model()->findByAttributes(array('modelName' => 'Quote', 'fieldName' => 'status'));
		$dropdown = Dropdowns::model()->findByPk($field->linkType);
		return CJSON::decode($dropdown->options, true);

		/*
		  return array(
		  'Draft'=>Yii::t('quotes','Draft'),
		  'Presented'=>Yii::t('quotes','Presented'),
		  "Issued"=>Yii::t('quotes','Issued'),
		  "Won"=>Yii::t('quotes','Won')
		  ); */
	}

	/**
	 * Generates markup for a quote line items table.
	 *
	 * @param type $emailTable Style hooks for emailing the quote
	 * @return string
	 */
	public function productTable($emailTable = false) {
		$pad = 4;
		// Declare styles
		$tableStyle = 'border-collapse: collapse; width: 100%;';
		$thStyle = 'padding: 5px; border: 1px solid black; background:#eee;';
		$thProductStyle = $thStyle;
		if(!$emailTable)
			$tableStyle .= 'display: inline;';
		else
			$thProductStyle .=  "width:60%;";
		$defaultStyle =  'padding: 5px;border-spacing:0;';
		$tdStyle = "$defaultStyle;border-left: 1px solid black; border-right: 1px solid black;";
		$tdFooterStyle = "$tdStyle;border-bottom: 1px solid black";
		$tdBoxStyle = "$tdFooterStyle;border-top: 1px solid black";

		// Declare element templates
		$thProduct = '<th style="'.$thProductStyle.'">{c}</th>';
		$tdDef = '<td style="'.$defaultStyle.'">{c}</td>';
		$th = '<th style="'.$thStyle.'">{c}</th>';
		$td = '<td style="'.$tdStyle.'">{c}</td>';
		$tdFooter = '<td style="'.$tdFooterStyle.'">{c}</td>';
		$tdBox = '<td style="'.$tdBoxStyle.'">{c}</td>';
		$hr = '<hr style="width: 100%;height:2px;background:black;" />';
		$tr = '<tr>{c}</tr>';
		$colRange = range(2,7);
		$span = array_combine($colRange,array_map(function($s){return "<td colspan=\"$s\"></td>";},$colRange));
		$span[1] = '<td></td>';

		$markup = array();

		// Table opening and header
		$markup[] = "<table style=\"$tableStyle\"><thead>";
		$row = array(str_replace('{c}',Yii::t('products','Line Item'),$thProduct));
		foreach(array('Unit Price','Quantity','Adjustment','Comments','Price') as $columnHeader) {
			$row[] = str_replace('{c}',Yii::t('products',$columnHeader),$th);
		}
		$markup[] = str_replace('{c}',implode("\n",$row),$tr);

		// Table header ending and body
		$markup[] = "</thead>";

		// Number of non-adjustment line items:
		$n_li = count($this->productLines);
		$i = 1;

		// Run through line items:
		$markup[] = '<tbody>';
		foreach($this->productLines as $ln=>$li) {
			// Begin row.
			$row = array();
			// Add columns for this line
			foreach(array('name','price','quantity','adjustment','description','total') as $attr) {
				$row[] = str_replace('{c}',$li->renderAttribute($attr),($i==$n_li?$tdFooter:$td));
			}
			// Row done.
			$markup[] = str_replace('{c}',implode('',$row),$tr);
			$i++;
		}

		$markup[] = '</tbody>';
		$markup[] = '<tbody>';
		// The subtotal and adjustment rows, if applicable:
		$i = 1;
		$n_adj = count($this->adjustmentLines);

		if($n_adj) {
			// Subtotal:
			$row = array($span[$pad]);
			$row[] = str_replace('{c}','<strong>'.Yii::t('quotes','Subtotal').'</strong>',$tdDef);
			$row[] = str_replace('{c}','<strong>'.Yii::app()->locale->numberFormatter->formatCurrency($this->subtotal,$this->currency).'</strong>',$tdDef);
			$markup[] = str_replace('{c}',implode('',$row),$tr);
			$markup[] = '</tbody>';
			// Adjustments:
			$markup[] = '<tbody>';
			foreach($this->adjustmentLines as $ln => $li) {
				// Begin row
				$row = array($span[$pad]);
				$row[] = str_replace('{c}',$li->renderAttribute('name').(!empty($li->description) ? ' ('.$li->renderAttribute('description').')':''),$tdDef);
				$row[] = str_replace('{c}',$li->renderAttribute('adjustment'),$tdDef);
				// Row done
				$markup[] = str_replace('{c}',implode('',$row),$tr);
				$i++;
			}
			$markup[] = '</tbody>';
			$markup[] = '<tbody>';
		}

		// Total:
		$row = array($span[$pad]);
		$row[] = str_replace('{c}','<strong>'.Yii::t('quotes','Total').'</strong>',$tdDef);
		$row[] = str_replace('{c}','<strong>'.Yii::app()->locale->numberFormatter->formatCurrency($this->total,$this->currency).'</strong>',$tdBox);
		$markup[] = str_replace('{c}',implode('',$row),$tr);
		$markup[] = '</tbody>';

		// Done.
		$markup[] = '</table>';

		return implode("\n",$markup);
	}

	public static function getNames() {

		$names = array(0 => "None");

		foreach (Yii::app()->db->createCommand()->select('id,name')->from('x2_quotes')->queryAll(false) as $row)
			$names[$row[0]] = $row[1];

		return $names;
	}

	public static function parseUsers($userArray) {
		return implode(', ', $userArray);
	}

	public static function parseUsersTwo($arr) {
		$str = "";
        if(is_array($arr)){
            $arr=array_keys($arr);
            $str=implode(', ',$arr);
        }
		$str = substr($str, 0, strlen($str) - 2);

		return $str;
	}

	public static function parseContacts($contactArray) {
		return implode(' ', $contactArray);
	}

	public static function parseContactsTwo($arr) {
		$str = "";
		foreach ($arr as $id => $contact) {
			$str.=$id . " ";
		}
		return $str;
	}

	public static function getQuotesLinks($accountId) {

		$quotesList = X2Model::model('Quote')->findAllByAttributes(array('accountName' => $accountId));
		// $quotesList = $this->model()->findAllByAttributes(array('accountId'),'=',array($accountId));

		$links = array();
		foreach ($quotesList as $model) {
			$links[] = CHtml::link($model->name, array('/quotes/quotes/view', 'id' => $model->id));
		}
		return implode(', ', $links);
	}

	public static function editContactArray($arr, $model) {

		$pieces = explode(" ", $model->associatedContacts);
		unset($arr[0]);

		foreach ($pieces as $contact) {
			if (array_key_exists($contact, $arr)) {
				unset($arr[$contact]);
			}
		}

		return $arr;
	}

	public static function editUserArray($arr, $model) {

		$pieces = explode(', ', $model->assignedTo);
		unset($arr['Anyone']);
		unset($arr['admin']);
		foreach ($pieces as $user) {
			if (array_key_exists($user, $arr)) {
				unset($arr[$user]);
			}
		}
		return $arr;
	}

	public static function editUsersInverse($arr) {

		$data = array();

		foreach ($arr as $username) {
			if ($username != '')
				$data[] = User::model()->findByAttributes(array('username' => $username));
		}

		$temp = array();
		if (isset($data)) {
			foreach ($data as $item) {
				if (isset($item))
					$temp[$item->username] = $item->firstName . ' ' . $item->lastName;
			}
		}
		return $temp;
	}

	public static function editContactsInverse($arr) {
		$data = array();

		foreach ($arr as $id) {
			if ($id != '')
				$data[] = X2Model::model('Contacts')->findByPk($id);
		}
		$temp = array();

		foreach ($data as $item) {
			$temp[$item->id] = $item->firstName . ' ' . $item->lastName;
		}
		return $temp;
	}

	public function search() {
		$criteria = new CDbCriteria;
		$parameters = array('limit' => ceil(ProfileChild::getResultsPerPage()));
		$criteria->scopes = array('findAll' => array($parameters));
		$criteria->addCondition("t.type!='invoice' OR t.type IS NULL");

		return $this->searchBase($criteria);
	}

	public function searchInvoice() {
		$criteria = new CDbCriteria;
		$parameters = array('limit' => ceil(ProfileChild::getResultsPerPage()));
		$criteria->scopes = array('findAll' => array($parameters));
		$criteria->addCondition("t.type='invoice'");

		return $this->searchBase($criteria);
	}

	public function searchAdmin() {
		$criteria = new CDbCriteria;

		return $this->searchBase($criteria);
	}

	public function searchBase($criteria) {

		$dateRange = Yii::app()->controller->partialDateRange($this->expectedCloseDate);
		if ($dateRange !== false)
			$criteria->addCondition('expectedCloseDate BETWEEN ' . $dateRange[0] . ' AND ' . $dateRange[1]);

		$dateRange = Yii::app()->controller->partialDateRange($this->createDate);
		if ($dateRange !== false)
			$criteria->addCondition('createDate BETWEEN ' . $dateRange[0] . ' AND ' . $dateRange[1]);

		$dateRange = Yii::app()->controller->partialDateRange($this->lastUpdated);
		if ($dateRange !== false)
			$criteria->addCondition('lastUpdated BETWEEN ' . $dateRange[0] . ' AND ' . $dateRange[1]);

		return parent::searchBase($criteria);
	}

	/**
	 * Get all active products indexed by their id,
	 * and any inactive products still in this quote
	 */
	public function productNames() {
		$products = Product::model()->findAll(
				array(
					'select' => 'id, name',
					'condition' => 'status=:active',
					'params' => array(':active' => 'Active'),
				)
		);
		$productNames = array(0 => '');
		foreach ($products as $product)
			$productNames[$product->id] = $product->name;

		// get any inactive products in this quote
		$quoteProducts = QuoteProduct::model()->findAll(
				array(
					'select' => 'productId, name',
					'condition' => 'quoteId=:quoteId',
					'params' => array(':quoteId' => $this->id),
				)
		);
		foreach ($quoteProducts as $qp)
			if (!isset($productNames[$qp->productId]))
				$productNames[$qp->productId] = $qp->name;

		return $productNames;
	}

	public function productPrices() {
		$products = Product::model()->findAll(
				array(
					'select' => 'id, price',
					'condition' => 'status=:active',
					'params' => array(':active' => 'Active'),
				)
		);
		$productPrices = array(0 => '');
		foreach ($products as $product)
			$productPrices[$product->id] = $product->price;

		// get any inactive products in this quote
		$quoteProducts = QuoteProduct::model()->findAll(
				array(
					'select' => 'productId, price',
					'condition' => 'quoteId=:quoteId',
					'params' => array(':quoteId' => $this->id),
				)
		);
		foreach ($quoteProducts as $qp)
			if (!isset($productPrices[$qp->productId]))
				$productPrices[$qp->productId] = $qp->price;

		return $productPrices;
	}

	public function activeProducts() {
		$products = Product::model()->findAllByAttributes(array('status' => 'Active'));
		$inactive = Product::model()->findAllByAttributes(array('status' => 'Inactive'));
		$quoteProducts = QuoteProduct::model()->findAll(
				array(
					'select' => 'productId',
					'condition' => 'quoteId=:quoteId',
					'params' => array(':quoteId' => $this->id),
				)
		);
		foreach ($quoteProducts as $qp)
			foreach ($inactive as $i)
				if ($qp->productId == $i->id)
					$products[] = $i;
		return $products;
	}

	/**
	 * Clear out records associated with this quote before deletion.
	 */
	public function beforeDelete(){

		QuoteProduct::model()->deleteAllByAttributes(array('quoteId'=>$this->id));
		Relationships::model()->deleteAllByAttributes(array('firstType' => 'quotes', 'firstId' => $this->id));// delete associated actions
		Actions::model()->deleteAllByAttributes(array('associationId'=>$this->id, 'associationType'=>'quotes'));
//		$event = new Events;
//		$event->type = 'record_deleted';
//		$event->subtype = 'quote';
//		$event->associationType = $this->myModelName;
//		$event->associationId = $this->id;
//		$event->text = $this->name;
//		$event->user = $this->assignedTo;
//		$event->save();
		$name = $this->name;
		// generate action record, for history
		$contact = $this->contact;
		if(!empty($contact)){
			$action = new Actions;
			$action->associationType = 'contacts';
			$action->type = 'quotes';
			$action->associationId = $contact->id;
			$action->associationName = $contact->name;
			$action->assignedTo = Yii::app()->getSuModel()->username; //  Yii::app()->user->getName();
			$action->completedBy = Yii::app()->getSuModel()->username; // Yii::app()->user->getName();
			$action->createDate = time();
			$action->dueDate = time();
			$action->completeDate = time();
			$action->visibility = 1;
			$action->complete = 'Yes';
			$action->actionDescription = "Deleted Quote: <span style=\"font-weight:bold;\">{$this->id}</span> {$this->name}";
			$action->save(); // Save after deletion of the model so that this action itself doensn't get deleted
		}
		return parent::beforeDelete();
	}
}
