<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerbweb.com	    http://www.webgroupmedia.com/
 ***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerb Development Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your inbox that you probably
 * haven't had since spammers found you in a game of 'E-mail Battleship'.
 * Miss. Miss. You sunk my inbox!
 *
 * A legitimate license entitles you to support from the developers,
 * and the warm fuzzy feeling of feeding a couple of obsessed developers
 * who want to help you get more done.
 *
 \* - Jeff Standen, Darren Sugita, Dan Hildebrandt
 *	 Webgroup Media LLC - Developers of Cerb
 */

class DAO_TimeTrackingActivity extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO timetracking_activity () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'timetracking_activity', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_TimeTrackingActivity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name ".
			"FROM timetracking_activity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name ASC";
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TimeTrackingActivity	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_TimeTrackingActivity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TimeTrackingActivity();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM timetracking_activity WHERE id IN (%s)", $ids_list));
		
		return true;
	}
};

class Model_TimeTrackingActivity {
	public $id;
	public $name;
};

class DAO_TimeTrackingEntry extends Cerb_ORMHelper {
	const ID = 'id';
	const TIME_ACTUAL_MINS = 'time_actual_mins';
	const LOG_DATE = 'log_date';
	const WORKER_ID = 'worker_id';
	const ACTIVITY_ID = 'activity_id';
	const IS_CLOSED = 'is_closed';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO timetracking_entry () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TIMETRACKING, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'timetracking_entry', $fields);
			
			// Send events
			if($check_deltas) {
				// Local events
				self::_processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.timetracking.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TIMETRACKING, $batch_ids);
			}
		}
	}
	
	static function _processUpdateEvents($ids, $change_fields) {
		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_TimeTrackingEntry::IS_CLOSED,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_TIMETRACKING, $ids)))
			return;
		
		if(false == ($models = DAO_TimeTrackingEntry::getIds($ids)))
			return;
		
		foreach($models as $id => $model) {
			if(!isset($before_models[$id]))
				continue;
			
			$before_model = (object) $before_models[$id];
			
			/*
			 * Activity Log: Time tracking status change
			 */
			
			@$is_closed = $change_fields[DAO_TimeTrackingEntry::IS_CLOSED];
			
			if($is_closed == $before_model->is_closed)
				unset($change_fields[DAO_TimeTrackingEntry::IS_CLOSED]);
			
			if(isset($change_fields[DAO_TimeTrackingEntry::IS_CLOSED])) {
				
				$status_to = null;
				$activity_point = null;
				
				if($model->is_closed) {
					$status_to = 'closed';
					$activity_point = 'timetracking.status.closed';
					
				} else {
					$status_to = 'open';
					$activity_point = 'timetracking.status.open';
					
				}
				
				if(!empty($status_to) && !empty($activity_point)) {
					/*
					 * Log activity (timetracking.status.*)
					 */
					$entry = array(
						//{{actor}} changed time tracking {{target}} to status {{status}}
						'message' => 'activities.timetracking.status',
						'variables' => array(
							'target' => sprintf("%s", $model->getSummary()),
							'status' => $status_to,
							),
						'urls' => array(
							'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TIMETRACKING, $model->id, $model->getSummary()),
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TIMETRACKING, $model->id, $entry);
				}
				
			} //foreach
		}
		
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('timetracking_entry', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_TimeTrackingEntry[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, time_actual_mins, log_date, worker_id, activity_id, is_closed ".
			"FROM timetracking_entry ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TimeTrackingEntry	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_TimeTrackingEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TimeTrackingEntry();
			$object->id = $row['id'];
			$object->time_actual_mins = $row['time_actual_mins'];
			$object->log_date = $row['log_date'];
			$object->worker_id = $row['worker_id'];
			$object->activity_id = $row['activity_id'];
			$object->is_closed = $row['is_closed'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOneSlave("SELECT count(id) FROM timetracking_entry");
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Entries
		$db->ExecuteMaster(sprintf("DELETE FROM timetracking_entry WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_TIMETRACKING,
					'context_ids' => $ids
				)
			)
		);

		return true;
	}

	static function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_TIMETRACKING,
					'context_table' => 'timetracking_entry',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function random() {
		return self::_getRandom('timetracking_entry');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
			$fields = SearchFields_TimeTrackingEntry::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);

		$select_sql = sprintf("SELECT ".
			"tt.id as %s, ".
			"tt.time_actual_mins as %s, ".
			"tt.log_date as %s, ".
			"tt.worker_id as %s, ".
			"tt.activity_id as %s, ".
			"tt.is_closed as %s ",
			SearchFields_TimeTrackingEntry::ID,
			SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS,
			SearchFields_TimeTrackingEntry::LOG_DATE,
			SearchFields_TimeTrackingEntry::WORKER_ID,
			SearchFields_TimeTrackingEntry::ACTIVITY_ID,
			SearchFields_TimeTrackingEntry::IS_CLOSED
		);
		
		$join_sql =
			"FROM timetracking_entry tt ".
		
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.timetracking' AND context_link.to_context_id = tt.id) " : " ")
			;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'tt.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		// Translate virtual fields
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_TimeTrackingEntry', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 'tt',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		$from_context = 'cerberusweb.contexts.timetracking';
		$from_index = 'tt.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		switch($param_key) {
			case SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT:
				$search = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID);
				$query = $search->getQueryFromParam($param);
				
				if(false === ($ids = $search->query($query, array('context_crc32' => sprintf("%u", crc32($from_context)))))) {
					$args['where_sql'] .= 'AND 0 ';

				} elseif(is_array($ids)) {
					$from_ids = DAO_Comment::getContextIdsByContextAndIds($from_context, $ids);
					
					$args['where_sql'] .= sprintf('AND %s IN (%s) ',
						$from_index,
						implode(', ', (!empty($from_ids) ? $from_ids : array(-1)))
					);
					
				} elseif(is_string($ids)) {
					$db = DevblocksPlatform::getDatabaseService();
					$temp_table = sprintf("_tmp_%s", uniqid());
					
					$db->ExecuteSlave(sprintf("CREATE TEMPORARY TABLE %s (PRIMARY KEY (id)) SELECT DISTINCT context_id AS id FROM comment INNER JOIN %s ON (%s.id=comment.id)",
						$temp_table,
						$ids,
						$ids
					));
					
					$args['join_sql'] .= sprintf("INNER JOIN %s ON (%s.id=tt.id) ",
						$temp_table,
						$temp_table
					);
				}
				
				break;
			
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;

			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY tt.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_TimeTrackingEntry::ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT tt.id) " : "SELECT COUNT(tt.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class Model_TimeTrackingEntry {
	public $id;
	public $time_actual_mins;
	public $log_date;
	public $worker_id;
	public $activity_id;
	public $is_closed;
	
	function getSummary() {
		$translate = DevblocksPlatform::getTranslationService();
		$out = '';
		
		$activity = '';
		if(!empty($this->activity_id))
			$activity = DAO_TimeTrackingActivity::get($this->activity_id); // [TODO] Cache?

		$time_increment = DevblocksPlatform::strSecsToString(intval($this->time_actual_mins) * 60, 2);
		
		$who = 'A worker';
		if(null != ($worker = DAO_Worker::get($this->worker_id)))
			$who = $worker->getName();

		if(!empty($activity)) {
			$out = vsprintf($translate->_('timetracking.ui.tracked_desc'), array(
				$who,
				$time_increment,
				$activity->name
			));
			
		} else {
			$out = vsprintf("%s tracked %s", array(
				$who,
				$time_increment
			));
			
		}

		return $out;
	}
};

class SearchFields_TimeTrackingEntry {
	// TimeTracking_Entry
	const ID = 'tt_id';
	const TIME_ACTUAL_MINS = 'tt_time_actual_mins';
	const LOG_DATE = 'tt_log_date';
	const WORKER_ID = 'tt_worker_id';
	const ACTIVITY_ID = 'tt_activity_id';
	const IS_CLOSED = 'tt_is_closed';
	
	// Context Links
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Virtuals
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_owners';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'tt', 'id', $translate->_('timetracking_entry.id')),
			self::TIME_ACTUAL_MINS => new DevblocksSearchField(self::TIME_ACTUAL_MINS, 'tt', 'time_actual_mins', $translate->_('timetracking_entry.time_actual_mins'), Model_CustomField::TYPE_NUMBER),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'tt', 'log_date', $translate->_('timetracking_entry.log_date'), Model_CustomField::TYPE_DATE),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'tt', 'worker_id', $translate->_('timetracking_entry.worker_id'), Model_CustomField::TYPE_WORKER),
			self::ACTIVITY_ID => new DevblocksSearchField(self::ACTIVITY_ID, 'tt', 'activity_id', $translate->_('timetracking_entry.activity_id')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'tt', 'is_closed', $translate->_('timetracking_entry.is_closed'), Model_CustomField::TYPE_CHECKBOX),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'owners', $translate->_('common.watchers'), 'WS'),
				
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT'),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_TIMETRACKING,
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class View_TimeTracking extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'timetracking_entries';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('timetracking.activity.tab');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_TimeTrackingEntry::LOG_DATE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_TimeTrackingEntry::ID,
			SearchFields_TimeTrackingEntry::CONTEXT_LINK,
			SearchFields_TimeTrackingEntry::CONTEXT_LINK_ID,
			SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT,
			SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK,
			SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET,
			SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_TimeTrackingEntry::ID,
			SearchFields_TimeTrackingEntry::CONTEXT_LINK,
			SearchFields_TimeTrackingEntry::CONTEXT_LINK_ID,
		));
		
		$this->addParamsDefault(array(
			SearchFields_TimeTrackingEntry::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::IS_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_TimeTrackingEntry::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_TimeTrackingEntry', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TimeTrackingEntry', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				case SearchFields_TimeTrackingEntry::IS_CLOSED:
				case SearchFields_TimeTrackingEntry::WORKER_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3)) {
						$pass = $this->_canSubtotalCustomField($field_key);
					}
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$activities = DAO_TimeTrackingActivity::getWhere();
				$label_map = array(
					'0' => '(none)',
				);
				
				foreach($activities as $activity_id => $activity) {
					$label_map[$activity_id] = $activity->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_TimeTrackingEntry', $column, $label_map, DevblocksSearchCriteria::OPER_IN, 'options[]');
				break;
				
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_TimeTrackingEntry', $column);
				break;
				
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$label_map = array();
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForNumberColumn('DAO_TimeTrackingEntry', $column, $label_map, 'in', 'worker_id[]');
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_TimeTrackingEntry', CerberusContexts::CONTEXT_TIMETRACKING, $column);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_TimeTrackingEntry', CerberusContexts::CONTEXT_TIMETRACKING, $column);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_TimeTrackingEntry', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_TimeTrackingEntry', $column, 'tt.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	// [TODO] activity, timeElapsed
	
	function getQuickSearchFields() {
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::LOG_DATE),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::ID),
				),
			'isClosed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::IS_CLOSED),
				),
			'mins' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::WORKER_ID),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_TIMETRACKING, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['_fulltext']['examples'] = $ft_examples;
			$fields['comments']['examples'] = $ft_examples;
		}
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				case 'timeElapsed':
					$field_keys = array(
						'timeElapsed' => SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS,
					);
					
					@$field_key = $field_keys[$k];
					
					$oper_hint = 0;
					
					if(preg_match('#^([\!\=\>\<]+)(.*)#', $v, $matches)) {
						$oper_hint = trim($matches[1]);
						$v = trim($matches[2]);
					}
					
					// [TODO] This could be a placeholder
					// [TODO] This should be unit tested
					$elapsed = DevblocksPlatform::strTimeToSecs($v);
					
					$value = $oper_hint . $elapsed;
					
					if($field_key && false != ($param = DevblocksSearchCriteria::getNumberParamFromQuery($field_key, $value)))
						$params[$field_key] = $param;
					break;
			}
		}
		
		$this->renderPage = 0;
		$this->addParams($params, true);
		
		return $params;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$activities = DAO_TimeTrackingActivity::getWhere(); // [TODO] getAll cache
		$tpl->assign('activities', $activities);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.timetracking::timetracking/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_TimeTrackingEntry::ID:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_TimeTrackingEntry::LOG_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_TIMETRACKING);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
			case SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$options = array(
					'0' => '(None)',
				);
				$activities = DAO_TimeTrackingActivity::getWhere();

				foreach($activities as $activity_id => $activity) { /* @var $activity Model_TimeTrackingActivity */
					$options[$activity_id] = $activity->name;
				}
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;

			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$activities = DAO_TimeTrackingActivity::getWhere(); // [TODO] getAll cache
				$strings = array();

				if(empty($values)) {
					
				} else {
					foreach($values as $val) {
						if(empty($val)) {
							$strings[] = "(none)";
						} else {
							if(!isset($activities[$val]))
								continue;
							$strings[] = $activities[$val]->name;
						}
					}
					echo implode(", ", $strings);
				}
				
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_TimeTrackingEntry::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			case SearchFields_TimeTrackingEntry::ID:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_TimeTrackingEntry::LOG_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
			case SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_closed':
					$change_fields[DAO_TimeTrackingEntry::IS_CLOSED] = $v;
					break;
				case 'activity_id':
					$change_fields[DAO_TimeTrackingEntry::ACTIVITY_ID] = $v;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_TimeTrackingEntry::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_TimeTrackingEntry::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_TimeTrackingEntry::update($batch_ids, $change_fields);

			// Watchers
			if(isset($do['watchers']) && is_array($do['watchers'])) {
				$watcher_params = $do['watchers'];
				foreach($batch_ids as $batch_id) {
					if(isset($watcher_params['add']) && is_array($watcher_params['add']))
						CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TIMETRACKING, $batch_id, $watcher_params['add']);
					if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
						CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TIMETRACKING, $batch_id, $watcher_params['remove']);
				}
			}
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TIMETRACKING, $custom_fields, $batch_ids);
			
			// Scheduled behavior
			if(isset($do['behavior']) && is_array($do['behavior'])) {
				$behavior_id = $do['behavior']['id'];
				@$behavior_when = strtotime($do['behavior']['when']) or time();
				@$behavior_params = isset($do['behavior']['params']) ? $do['behavior']['params'] : array();
				
				if(!empty($batch_ids) && !empty($behavior_id))
				foreach($batch_ids as $batch_id) {
					DAO_ContextScheduledBehavior::create(array(
						DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
						DAO_ContextScheduledBehavior::CONTEXT => CerberusContexts::CONTEXT_TIMETRACKING,
						DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
						DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
						DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
					));
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_TimeTracking extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	function getRandom() {
		return DAO_TimeTrackingEntry::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=time_tracking&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$time_entry = DAO_TimeTrackingEntry::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		
		$summary = $time_entry->getSummary();
		$friendly = DevblocksPlatform::strToPermalink($summary);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $time_entry->id,
			'name' => $summary,
			'permalink' => $url,
		);
	}

	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'log_date',
			'mins',
			'is_closed',
			'worker__label',
		);
	}
	
	function getContext($timeentry, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Time Entry:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING);
		
   		// Polymorph
		if(is_numeric($timeentry)) {
			$timeentry = DAO_TimeTrackingEntry::get($timeentry);
		} elseif($timeentry instanceof Model_TimeTrackingEntry) {
			// It's what we want already.
		} elseif(is_array($timeentry)) {
			$timeentry = Cerb_ORMHelper::recastArrayToModel($timeentry, 'Model_TimeTrackingEntry');
		} else {
			$timeentry = null;
		}
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'is_closed' => $prefix.$translate->_('common.is_closed'),
			'log_date' => $prefix.$translate->_('timetracking_entry.log_date'),
			'mins' => $prefix.$translate->_('timetracking_entry.time_actual_mins'),
			'summary' => $prefix.$translate->_('common.summary'),
				
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'log_date' => Model_CustomField::TYPE_DATE,
			'mins' => 'time_mins',
			'summary' => Model_CustomField::TYPE_SINGLE_LINE,
				
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		$blank = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_TIMETRACKING;
		$token_values['_types'] = $token_types;
		
		if(null != $timeentry) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $timeentry->getSummary();
			$token_values['log_date'] = $timeentry->log_date;
			$token_values['id'] = $timeentry->id;
			$token_values['mins'] = $timeentry->time_actual_mins;
			$token_values['summary'] = $timeentry->getSummary();
			$token_values['is_closed'] = $timeentry->is_closed;
			$token_values['activity_id'] = $timeentry->activity_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($timeentry, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=time_tracking&id=%d-%s",$timeentry->id, DevblocksPlatform::strToPermalink($timeentry->getSummary())), true);
			
			// Worker
			@$worker_id = $timeentry->worker_id;
			$token_values['worker_id'] = $worker_id;
		}
		
		// Worker
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, null, true);

			// Clear dupe labels
			CerberusContexts::scrubTokensWithRegexp(
				$merge_token_labels,
				$blank, // ignore
				array(
					"#^address_first_name$#",
					"#^address_full_name$#",
					"#^address_last_name$#",
				)
			);
		
			CerberusContexts::merge(
				'worker_',
				$prefix,
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_TIMETRACKING;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Time Tracking';
		$view->view_columns = array(
			SearchFields_TimeTrackingEntry::LOG_DATE,
		);
		$view->addParams(array(
			SearchFields_TimeTrackingEntry::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Time Tracking';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::CONTEXT_LINK_ID,'=',$context_id),
			);
		}

		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$id = $context_id; // [TODO] Cleanup
		
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('view_id', $view_id);
		
		/*
		 * This treats procedurally created model objects
		 * the same as existing objects
		 */
		if(!empty($id)) { // Were we given a model ID to load?
			if(null != ($model = DAO_TimeTrackingEntry::get($id)))
				$tpl->assign('model', $model);
			
		} else {
			$model = new Model_TimeTrackingEntry();
			$model->log_date = time();

			// Initial time
			
			@$total_mins = DevblocksPlatform::importGPC($_REQUEST['mins'],'integer',0);
			$model->time_actual_mins = $total_mins;
			
			// If we're linking a context during creation
			
			@$link_context = strtolower($_SESSION['timetracking_context']);
			@$link_context_id = intval($_SESSION['timetracking_context_id']);
			
			/* If the session was empty, don't set these since they may have been
			 * previously set by the abstract context peek code.
			 */
			
			if(!empty($link_context)) {
				$tpl->assign('link_context', $link_context);
				$tpl->assign('link_context_id', $link_context_id);
			}
			
			// Template
			
			$tpl->assign('model', $model);
		}

		/* @var $model Model_TimeTrackingEntry */
		
		// Activities
		// [TODO] Cache
		$activities = DAO_TimeTrackingActivity::getWhere();
		$tpl->assign('activities', $activities);
		
		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TIMETRACKING, $id);
		$comments = array_reverse($comments, true);
		$tpl->assign('comments', $comments);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING, false);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TIMETRACKING, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('devblocks:cerberusweb.timetracking::timetracking/peek.tpl');
	}
};