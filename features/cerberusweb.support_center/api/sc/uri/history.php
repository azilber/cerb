<?php
class UmScHistoryController extends Extension_UmScController {
	const PARAM_WORKLIST_COLUMNS_JSON = 'history.worklist.columns';
	
	function isVisible() {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		return !empty($active_contact);
	}
	
	function renderSidebar(DevblocksHttpResponse $response) {
//		$tpl = DevblocksPlatform::getTemplateService();
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // history
		$mask = array_shift($stack);

		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		if(empty($mask)) {
			// Ticket history
			if(null == ($history_view = UmScAbstractViewLoader::getView('', 'sc_history_list'))) {
				$history_view = new UmSc_TicketHistoryView();
				$history_view->id = 'sc_history_list';
				$history_view->name = "";
				$history_view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
				$history_view->renderSortAsc = false;
				$history_view->renderLimit = 10;
				
				$history_view->addParams(array(
					new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'in',array('open','waiting')),
				), true);
			}
			
			@$params_columns = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_WORKLIST_COLUMNS_JSON, '[]', true);
			
			if(empty($params_columns))
				$params_columns = array(
					SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
				);
				
			$history_view->view_columns = $params_columns;
			
			// Lock to current visitor
			$history_view->addParamsRequired(array(
				'_acl_reqs' => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',$shared_address_ids),
				'_acl_status' => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			), true);
			
			UmScAbstractViewLoader::setView($history_view->id, $history_view);
			$tpl->assign('view', $history_view);

			$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/history/index.tpl");
			
		} else {
			// Secure retrieval (address + mask)
			list($tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
					new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',$shared_address_ids),
				),
				1,
				0,
				null,
				null,
				false
			);
			$ticket = array_shift($tickets);
			
			// Security check (mask compare)
			if(0 == strcasecmp($ticket[SearchFields_Ticket::TICKET_MASK],$mask)) {
				$messages = DAO_Message::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
				$messages = array_reverse($messages, true);
				$attachments = array();
				
				// Attachments
				if(is_array($messages) && !empty($messages)) {
					// Populate attachments per message
					foreach($messages as $message_id => $message) {
						$map = $message->getLinksAndAttachments();
						
						if(!isset($map['links']) || empty($map['links'])
							|| !isset($map['attachments']) || empty($map['attachments']))
							continue;
						
						foreach($map['links'] as $link_id => $link) {
							$file = $map['attachments'][$link->attachment_id];
							
							if(empty($file)) {
								unset($map['links'][$link_id]);
								continue;
							}
								
							if(0 == strcasecmp('original_message.html', $file->display_name)) {
								unset($map['links'][$link_id]);
								unset($map['files'][$link->attachment_id]);
								continue;
							}
						}
						
						if(!empty($map)) {
							if(!isset($attachments[$message_id]))
								$attachments[$message_id] = array();
							
							$attachments[$message_id][$link->guid] = $map;
						}
					}
				}
				
				$tpl->assign('ticket', $ticket);
				$tpl->assign('messages', $messages);
				$tpl->assign('attachments', $attachments);
				
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/history/display.tpl");
			}
		}
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		$params = array(
			'columns' => DAO_CommunityToolProperty::get($instance->code, self::PARAM_WORKLIST_COLUMNS_JSON, '[]', true),
		);
		$tpl->assign('history_params', $params);
		
		$view = new View_Ticket();
		
		$columns = array_filter(
			$view->getColumnsAvailable(),
			function($column) {
				return !empty($column->db_label);
			}
		);
		
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		$tpl->assign('history_columns', $columns);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/module/history.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$columns = DevblocksPlatform::importGPC($_POST['history_columns'],'array',array());

		$columns = array_filter($columns, function($column) {
			return !empty($column);
		});
		
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_WORKLIST_COLUMNS_JSON, $columns, true);
	}
	
	function saveTicketPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer','0');
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);

		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_ADDRESS, $active_contact->email_id);
		
		if(false == ($ticket = DAO_Ticket::getTicketByMask($mask)))
			return;
		
		// Only allow access if mask has one of the valid requesters
		$requesters = $ticket->getRequesters();
		$allowed_requester_ids = array_intersect(array_keys($requesters), $shared_address_ids);
		
		if(empty($allowed_requester_ids))
			return;
		
		$fields = array(
			DAO_Ticket::IS_CLOSED => ($closed) ? 1 : 0
		);
		DAO_Ticket::update($ticket->id, $fields);
		
		CerberusContexts::popActivityDefaultActor();
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'history', $ticket->mask)));
	}
	
	function doReplyAction() {
		@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$umsession = ChPortalHelper::getSession();
		if(false == ($active_contact = $umsession->getProperty('sc_login', null)))
			return false;
		
		// Load contact addresses
		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		// Validate FROM address
		if(null == ($from_address = DAO_Address::lookupAddress($from, false)))
			return false;
		
		if($from_address->contact_person_id != $active_contact->id)
			return false;
		
		if(false == ($ticket = DAO_Ticket::getTicketByMask($mask)))
			return;
		
		// Only allow access if mask has one of the valid requesters
		$requesters = $ticket->getRequesters();
		$allowed_requester_ids = array_intersect(array_keys($requesters), $shared_address_ids);
		
		if(empty($allowed_requester_ids))
			return;
		
		$messages = DAO_Message::getMessagesByTicket($ticket->id);
		$last_message = array_pop($messages); /* @var $last_message Model_Message */
		$last_message_headers = $last_message->getHeaders();
		unset($messages);

		// Ticket group settings
		$group = DAO_Group::get($ticket->group_id);
		@$group_replyto = $group->getReplyTo($ticket->bucket_id);
		
		// Headers
		$message = new CerberusParserMessage();
		$message->headers['from'] = $from_address->email;
		$message->headers['to'] = $group_replyto->email;
		$message->headers['date'] = date('r');
		$message->headers['subject'] = 'Re: ' . $ticket->subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		$message->headers['in-reply-to'] = @$last_message_headers['message-id'];
		
		$message->body = sprintf(
			"%s",
			$content
		);

		// Attachments
		if(is_array($_FILES) && !empty($_FILES))
		foreach($_FILES as $name => $files) {
			// field[]
			if(is_array($files['name'])) {
				foreach($files['name'] as $idx => $name) {
					if(empty($name))
						continue;
					
					$attach = new ParserFile();
					$attach->setTempFile($files['tmp_name'][$idx],'application/octet-stream');
					$attach->file_size = filesize($files['tmp_name'][$idx]);
					$message->files[$name] = $attach;
				}
				
			} else {
				if(!isset($files['name']) || empty($files['name']))
					continue;
				
				$attach = new ParserFile();
				$attach->setTempFile($files['tmp_name'],'application/octet-stream');
				$attach->file_size = filesize($files['tmp_name']);
				$message->files[$files['name']] = $attach;
			}
		}
		
		CerberusParser::parseMessage($message, array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal', ChPortalHelper::getCode(), 'history', $ticket->mask)));
	}
};

class UmSc_TicketHistoryView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_history';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_SUBJECT,
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
		);
		
		$this->addParamsHidden(array(
			SearchFields_Ticket::TICKET_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$columns = array_merge($this->view_columns, array($this->renderSortBy));
		
		$objects = DAO_Ticket::search(
			$columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/history/view.tpl");
	}

	function getFields() {
		return SearchFields_Ticket::getFields();
	}
	
	function getSearchFields() {
		$fields = SearchFields_Ticket::getFields();

		foreach($fields as $key => $field) {
			switch($key) {
				case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				case SearchFields_Ticket::REQUESTER_ID:
				case SearchFields_Ticket::TICKET_MASK:
				case SearchFields_Ticket::TICKET_SUBJECT:
				case SearchFields_Ticket::TICKET_CREATED_DATE:
				case SearchFields_Ticket::TICKET_UPDATED_DATE:
				case SearchFields_Ticket::VIRTUAL_STATUS:
					break;
				default:
					unset($fields[$key]);
			}
		}
		
		return $fields;
	}
	
	function renderCriteria($field) {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
				$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__number.tpl');
				break;
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
				$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__date.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__bool.tpl');
				break;
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__fulltext.tpl');
				break;
			case SearchFields_Ticket::REQUESTER_ID:
				$shared_addresses = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, false);
				$tpl->assign('requesters', $shared_addresses);
				$tpl->display('devblocks:cerberusweb.support_center::support_center/history/criteria/requester.tpl');
				break;
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$tpl->display('devblocks:cerberusweb.support_center::support_center/history/criteria/status.tpl');
				break;
//			default:
//				// Custom Fields
//				if('cf_' == substr($field,0,3)) {
//					$this->_renderCriteriaCustomField($tpl, substr($field,3));
//				} else {
//					echo ' ';
//				}
//				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		$translate = DevblocksPlatform::getTranslationService();
		
		switch($field) {
			// Overload
			case SearchFields_Ticket::REQUESTER_ID:
				$strings = array();
				if(empty($values) || !is_array($values))
					break;
				$addresses = DAO_Address::getWhere(sprintf("%s IN (%s)", DAO_Address::ID, implode(',', $values)));
				
				foreach($values as $val) {
					if(isset($addresses[$val]))
						$strings[] = $addresses[$val]->email;
				}
				echo implode('</b> or <b>', $strings);
				break;
				
			// Overload
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'open':
							$strings[] = $translate->_('status.waiting');
							break;
						case 'waiting':
							$strings[] = $translate->_('status.open');
							break;
						case 'closed':
							$strings[] = $translate->_('status.closed');
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function doSetCriteria($field, $oper, $value) {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		
		$criteria = null;

		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				@$statuses = DevblocksPlatform::importGPC($_REQUEST['value'],'array',array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $statuses);
				break;
				
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
					$from = 0;
					
				if(empty($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
					$to = 'now';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Ticket::REQUESTER_ID:
				@$requester_ids = DevblocksPlatform::importGPC($_REQUEST['requester_ids'],'array',array());
				
				// If blank, this is pointless.
				if(empty($active_contact) || empty($requester_ids))
					break;
				
				$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
				if(empty($shared_address_ids))
					$shared_address_ids = array(-1);
					
				// Sanitize the selections to make sure they only include verified addresses on this contact
				$intersect = array_intersect(array_keys($shared_address_ids), $requester_ids);
				
				if(empty($intersect))
					break;
				
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$intersect);
				break;
				
//			default:
//				// Custom Fields
//				if(substr($field,0,3)=='cf_') {
//					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
//				}
//				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};